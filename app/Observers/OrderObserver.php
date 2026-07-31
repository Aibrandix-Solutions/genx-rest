<?php

namespace App\Observers;

use App\Events\OrderCancelled;
use App\Events\OrderSuccessEvent;
use App\Events\OrderUpdated;
use App\Events\TodayOrdersUpdated;
use App\Models\Kot;
use App\Models\Order;
use App\Models\Table;
use App\Services\RewardPointsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /** Attributes that kitchen/order UIs care about for live refresh. */
    private const BROADCAST_ATTRS = [
        'status',
        'order_status',
        'table_id',
        'waiter_id',
        'customer_id',
        'order_type',
        'order_type_id',
        'branch_id',
    ];

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
            $table = Table::find($order->table_id);
            if ($table) {
                $userId = $order->waiter_id ?? auth()->id();
                $result = $table->lockForOrder($userId, $order->id);

                if (! $result['success']) {
                    Log::warning('Failed to lock table for order', [
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'message' => $result['message'],
                    ]);
                }
            }
        }

        // Defer count + Pusher until after the HTTP response so POS KOT/print
        // is not blocked by the today-KOT join or VPS→Pusher latency.
        $this->dispatchBroadcastsAfterResponse(
            orderId: (int) $order->id,
            action: 'created',
            orderBecamePaid: false,
            notifyTodayOrders: true,
            forceRefreshKotCount: true
        );
    }

    public function updated(Order $order)
    {
        $order->loadMissing('branch.restaurant');
        $orderRestaurant = $order->branch?->restaurant;

        // Prefer wasChanged (post-save); fall back to isDirty while originals are still pre-sync.
        $statusChanged = $order->wasChanged('status') || $order->isDirty('status');
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        // Handle table unlock when order is billed or canceled
        if ($statusChanged && in_array($newStatus, ['billed', 'canceled'], true)) {
            if ($order->table_id && ($orderRestaurant?->enable_table_lock_on_order ?? false)) {
                $table = Table::find($order->table_id);
                if ($table) {
                    $result = $table->unlockFromOrder($order->id);

                    Log::info('Table unlock on order status change', [
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'unlock_result' => $result,
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

        // Skip expensive broadcasts on intermediate total/tax-only saves (common in POS store()).
        $shouldBroadcast = $statusChanged || $this->broadcastRelevantChange($order);
        if (! $shouldBroadcast) {
            return;
        }

        $this->dispatchBroadcastsAfterResponse(
            orderId: (int) $order->id,
            action: 'updated',
            orderBecamePaid: $statusChanged && $newStatus == 'paid' && $oldStatus != 'paid',
            notifyTodayOrders: $statusChanged,
            forceRefreshKotCount: $statusChanged
        );
    }

    public function deleted(Order $order): void
    {
        // If an order is deleted (e.g. POS deletes a draft/empty order), make sure we don't leave the table locked.
        $order->loadMissing('branch.restaurant');
        $orderRestaurant = $order->branch?->restaurant;

        if ($order->table_id && ($orderRestaurant?->enable_table_lock_on_order ?? false)) {
            $table = Table::find($order->table_id);
            if ($table) {
                $table->unlockFromOrder($order->id);
            }
        }
    }

    private function broadcastRelevantChange(Order $order): bool
    {
        foreach (self::BROADCAST_ATTRS as $attr) {
            if ($order->wasChanged($attr) || $order->isDirty($attr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cache briefly so multi-update POS saves (items → totals → billed) don't repeat the join.
     */
    private static function resolveTodayKotCount(bool $forceRefresh = false): int
    {
        $branchId = (int) (branch()?->id ?? 0);
        $cacheKey = 'pos.today_kot_count.'.$branchId.'.'.now()->toDateString();

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return (int) Cache::remember($cacheKey, 5, function () {
            return Kot::join('orders', 'kots.order_id', '=', 'orders.id')
                ->whereDate('kots.created_at', '>=', now()->startOfDay()->toDateTimeString())
                ->whereDate('kots.created_at', '<=', now()->endOfDay()->toDateTimeString())
                ->where('orders.status', '<>', 'canceled')
                ->where('orders.status', '<>', 'draft')
                ->count();
        });
    }

    private function dispatchBroadcastsAfterResponse(
        int $orderId,
        string $action,
        bool $orderBecamePaid,
        bool $notifyTodayOrders,
        bool $forceRefreshKotCount = false
    ): void {
        dispatch(function () use ($orderId, $action, $orderBecamePaid, $notifyTodayOrders, $forceRefreshKotCount) {
            $order = Order::query()->find($orderId);
            if (! $order) {
                return;
            }

            event(new OrderUpdated($order, $action));

            // Dashboard badge only needs refresh when order lifecycle changes.
            if ($notifyTodayOrders || $orderBecamePaid) {
                $todayKotCount = self::resolveTodayKotCount($forceRefreshKotCount);

                if ($notifyTodayOrders) {
                    event(new TodayOrdersUpdated($todayKotCount));
                }

                // Customer order-success page expects an integer count, not the Order model.
                if ($orderBecamePaid) {
                    event(new OrderSuccessEvent($todayKotCount));
                }
            }
        })->afterResponse();
    }
}
