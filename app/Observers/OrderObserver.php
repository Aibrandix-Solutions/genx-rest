<?php

namespace App\Observers;

use App\Models\Order;
use App\Events\OrderCancelled;
use App\Events\TodayOrdersUpdated;
use App\Models\Kot;
use App\Events\OrderUpdated;
use App\Events\OrderSuccessEvent;
use App\Services\RewardPointsService;


class OrderObserver
{

    public function creating(Order $order)
    {
        if (branch() && $order->branch_id == null) {
            $order->branch_id = branch()->id;
        }
    }

    public function created(Order $order)
    {
        $todayKotCount = Kot::join('orders', 'kots.order_id', '=', 'orders.id')
            ->whereDate('kots.created_at', '>=', now()->startOfDay()->toDateTimeString())
            ->whereDate('kots.created_at', '<=', now()->endOfDay()->toDateTimeString())
            ->where('orders.status', '<>', 'canceled')
            ->where('orders.status', '<>', 'draft')
            ->count();

        event(new OrderUpdated($order, 'created'));
        event(new TodayOrdersUpdated($todayKotCount));
    }

    public function updated(Order $order)
    {
        $statusChanged = $order->isDirty('status');
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        // Handle order cancellation - reverse reward points
        if ($statusChanged && $newStatus == 'canceled') {
            OrderCancelled::dispatch($order);
            
            // Reverse reward points if order was previously paid
            if ($oldStatus == 'paid' && $order->customer_id) {
                $rewardService = app(RewardPointsService::class);
                $rewardService->reverseOrderPoints($order);
            }
        }

        // Award reward points when order is marked as paid
        if ($statusChanged && $newStatus == 'paid' && $oldStatus != 'paid' && $order->customer_id) {
            $rewardService = app(RewardPointsService::class);
            $rewardService->awardPoints($order);
        }

        $todayKotCount = Kot::join('orders', 'kots.order_id', '=', 'orders.id')
            ->whereDate('kots.created_at', '>=', now()->startOfDay()->toDateTimeString())
            ->whereDate('kots.created_at', '<=', now()->endOfDay()->toDateTimeString())
            ->where('orders.status', '<>', 'canceled')
            ->where('orders.status', '<>', 'draft')
            ->count();

        event(new OrderUpdated($order, 'updated'));
        event(new TodayOrdersUpdated($todayKotCount));

        event(new OrderSuccessEvent($order));
    }
}
