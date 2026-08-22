<?php

namespace App\Services;

use App\Models\Order;

class RoomChargeOrderSettlement
{
    public const STATUS_OUTSTANDING = 'outstanding';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';

    public static function status(Order $order): string
    {
        $paid = self::settledAmount($order);
        $outstanding = self::outstandingAmount($order);

        if ($outstanding <= 0.0001) {
            return self::STATUS_PAID;
        }

        if ($paid > 0.0001) {
            return self::STATUS_PARTIALLY_PAID;
        }

        return self::STATUS_OUTSTANDING;
    }

    public static function settledAmount(Order $order): float
    {
        return round((float) $order->payments()->where('payment_method', '!=', 'due')->sum('amount'), 2);
    }

    public static function outstandingAmount(Order $order): float
    {
        return max(0, round((float) $order->total - self::settledAmount($order), 2));
    }

    public static function taxTotal(Order $order): float
    {
        $order->loadMissing('items');

        return round((float) $order->items->sum('tax_amount'), 2);
    }
}
