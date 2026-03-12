<?php

namespace App\Enums;

enum OrderStatus: string

{
    case PLACED = 'placed';
    // case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case FOOD_READY = 'food_ready'; // Food is ready (for customer site display)
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case OUT_FOR_DELIVERY = 'out_for_delivery'; // Order is being delivered
    case SERVED = 'served'; // Order served at table (for dine-in)
    case DELIVERED = 'delivered'; // Order delivered to the customer
    case CANCELLED = 'cancelled'; // Order cancelled

    public function label(): string
    {
        return match ($this) {
            // NOTE: This returns translation keys under `modules.order.*`
            self::PLACED => 'info_placed',
            self::CONFIRMED => 'info_confirmed',
            self::PREPARING => 'info_preparing',
            self::FOOD_READY => 'info_food_ready',
            self::READY_FOR_PICKUP => 'info_ready_for_pickup',
            self::OUT_FOR_DELIVERY => 'info_out_for_delivery',
            self::SERVED => 'info_served',
            self::DELIVERED => 'info_delivered',
            self::CANCELLED => 'info_cancelled',
        };
    }

    /**
     * Check if the package type is editable.
     *
     * @return bool
     */
    // public function isEditable(): bool
    // {
    //     return !in_array($this, [self::DELIVERED], true);
    // }

    /**
     * Check if the package type is deletable.
     *
     * @return bool
     */
    // public function isDeletable(): bool
    // {
    //     return !in_array($this, [self::DELIVERED], true);
    // }
}
