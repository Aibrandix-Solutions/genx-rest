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
        $order->loadMissing('branch.restaurant');
        $orderRestaurant = $order->branch?->restaurant;

        // Auto-lock table when order is created (if feature enabled and has table)
        if ($order->table_id && ($orderRestaurant?->enable_table_lock_on_order ?? false)) {
            $table = \App\Models\Table::find($order->table_id);
            if ($table) {
                $userId = $order->waiter_id ?? auth()->id();
                $result = $table->lockForOrder($userId, $order->id);
                
                if (!$result['success']) {
                    \Illuminate\Support\Facades\Log::warning('Failed to lock table for order', [
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'message' => $result['message']
                    ]);
                }
            }
        }

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
        $order->loadMissing('branch.restaurant');
        $orderRestaurant = $order->branch?->restaurant;

        $statusChanged = $order->isDirty('status');
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        // Handle table unlock when order is billed or canceled
        if ($statusChanged && in_array($newStatus, ['billed', 'canceled'])) {
            if ($order->table_id && ($orderRestaurant?->enable_table_lock_on_order ?? false)) {
                $table = \App\Models\Table::find($order->table_id);
                if ($table) {
                    $result = $table->unlockFromOrder($order->id);
                    
                    \Illuminate\Support\Facades\Log::info('Table unlock on order status change', [
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'unlock_result' => $result
                    ]);
                }
            }
        }

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

    public function deleted(Order $order): void
    {
        // If an order is deleted (e.g. POS deletes a draft/empty order), make sure we don't leave the table locked.
        $order->loadMissing('branch.restaurant');
        $orderRestaurant = $order->branch?->restaurant;

        if ($order->table_id && ($orderRestaurant?->enable_table_lock_on_order ?? false)) {
            $table = \App\Models\Table::find($order->table_id);
            if ($table) {
                $table->unlockFromOrder($order->id);
            }
        }
    }
}
