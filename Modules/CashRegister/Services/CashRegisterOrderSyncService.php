<?php

namespace Modules\CashRegister\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;

class CashRegisterOrderSyncService
{
    /**
     * Recalculate and upsert the cash sale transaction for an order
     */
    public static function syncCashForOrder(Order $order): void
    {
        // Only track paid orders
        $status = $order->status ?? $order->payment_status ?? null;
        $isPaid = ($status === 'paid');

        // Sum current cash payments for this order
        $order->loadMissing(['payments']);
        $cashAmount = 0.0;
        if (method_exists($order, 'payments')) {
            foreach ($order->payments as $payment) {
                if (($payment->payment_method ?? null) === 'cash') {
                    $cashAmount += (float) ($payment->amount ?? 0);
                }
            }
        }

        $userId = $order->created_by ?? auth()->id();
        if (!$userId) {
            return;
        }

        // Scope everything to the order's branch/restaurant
        $order->loadMissing(['branch']);
        $branchId = (int) ($order->branch_id ?? 0);
        $restaurantId = (int) ($order->branch?->restaurant_id ?? 0);
        if ($branchId <= 0 || $restaurantId <= 0) {
            return;
        }

        $session = CashRegisterSession::where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        // If not paid or no open session, remove any existing and stop
        if (!$isPaid || !$session) {
            CashRegisterTransaction::where('order_id', $order->id)
                ->where('type', 'cash_sale')
                ->delete();
            return;
        }

        // If no cash amount, remove existing transaction if any
        if ($cashAmount <= 0) {
            CashRegisterTransaction::where('order_id', $order->id)
                ->where('type', 'cash_sale')
                ->delete();
            return;
        }

        $now = now();

        // Concurrency-safe upsert (requires unique index on (order_id,type))
        DB::table('cash_register_transactions')->upsert(
            [[
                'order_id' => $order->id,
                'type' => 'cash_sale',
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $session->restaurant_id,
                'branch_id' => $session->branch_id,
                'happened_at' => $now,
                'reference' => (string) ($order->uuid ?? $order->id),
                'reason' => 'POS cash sale',
                'amount' => $cashAmount,
                'running_amount' => 0,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['order_id', 'type'],
            [
                'cash_register_session_id',
                'restaurant_id',
                'branch_id',
                'happened_at',
                'reference',
                'reason',
                'amount',
                'running_amount',
                'created_by',
                'updated_at',
            ]
        );
    }

    public static function syncPaidCashOrder(Order $order): void
    {
        // Accept both "status" and "payment_status" == paid semantics
        $status = $order->status ?? $order->payment_status ?? null;
        if ($status !== 'paid') {
            return;
        }

        // Determine cash payment from payments relation if available
        $isCash = false;
        if (method_exists($order, 'payments')) {
            foreach ($order->payments as $payment) {
                if (($payment->payment_method ?? null) === 'cash' && (float) ($payment->amount ?? 0) > 0) {
                    $isCash = true;
                    break;
                }
            }
        }

        if (!$isCash) {
            return;
        }

        $userId = $order->created_by ?? auth()->id();
        if (!$userId) {
            return;
        }

        $order->loadMissing(['branch']);
        $branchId = (int) ($order->branch_id ?? 0);
        $restaurantId = (int) ($order->branch?->restaurant_id ?? 0);
        if ($branchId <= 0 || $restaurantId <= 0) {
            return;
        }

        $session = CashRegisterSession::where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return; // no open session for user
        }

        // For paid orders, just delegate to recalculation logic
        self::syncCashForOrder($order);
    }
}


