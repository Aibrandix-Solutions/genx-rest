<?php

namespace App\Services;

use App\Models\Order;
use Modules\Hotel\Services\OrderFolioSettlement;

class RoomChargeOrderSettlement
{
    public const STATUS_OUTSTANDING = 'outstanding';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public static function status(Order $order): string
    {
        if (class_exists(OrderFolioSettlement::class) && OrderFolioSettlement::isFolioSettled($order)) {
            return self::STATUS_PAID;
        }

        if ($order->status === 'paid' || $order->isFullyPaid()) {
            return self::STATUS_PAID;
        }

        if (self::settledAmount($order) <= 0.01) {
            return self::STATUS_OUTSTANDING;
        }

        return self::STATUS_PARTIALLY_PAID;
    }

    public static function settledAmount(Order $order): float
    {
        if (class_exists(OrderFolioSettlement::class) && OrderFolioSettlement::isFolioSettled($order)) {
            return round((float) $order->total, 2);
        }

        return round($order->nonDuePaymentsSum(), 2);
    }

    public static function outstandingAmount(Order $order): float
    {
        if (class_exists(OrderFolioSettlement::class) && OrderFolioSettlement::isFolioSettled($order)) {
            return 0.0;
        }

        return round($order->outstandingAmount(), 2);
    }

    public static function taxTotal(Order $order): float
    {
        $order->loadMissing('items');

        return round((float) $order->items->sum('tax_amount'), 2);
    }
}
