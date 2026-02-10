<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelSetting extends Model
{
    use HasFactory, HasRestaurant;

    const MODE_HOTEL_PRIMARY = 'hotel_primary';
    const MODE_RESTAURANT_PRIMARY = 'restaurant_primary';
    const MODE_EQUAL = 'equal';

    protected $fillable = [
        'restaurant_id',
        'business_mode',
        'hotel_name',
        'hotel_logo',
        'default_check_in_time',
        'default_checkout_time',
        'early_checkin_charge_per_hour',
        'late_checkout_charge_per_hour',
        'payment_policy',
        'deposit_percentage',
        'cancellation_policy',
        'enable_room_service',
        'enable_housekeeping_module',
        'enable_dynamic_pricing',
        'max_rooms_per_booking',
        'tax_rate',
        'service_charge_rate',
    ];

    protected $casts = [
        'business_mode' => 'string',
        'early_checkin_charge_per_hour' => 'decimal:2',
        'late_checkout_charge_per_hour' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'enable_room_service' => 'boolean',
        'enable_housekeeping_module' => 'boolean',
        'enable_dynamic_pricing' => 'boolean',
        'max_rooms_per_booking' => 'integer',
        'tax_rate' => 'decimal:2',
        'service_charge_rate' => 'decimal:2',
    ];

    const PAYMENT_FULL_ADVANCE = 'full_advance';
    const PAYMENT_PARTIAL_DEPOSIT = 'partial_deposit';
    const PAYMENT_AT_CHECKOUT = 'pay_at_checkout';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Calculate deposit amount based on policy
     */
    public function calculateDeposit($totalAmount)
    {
        if ($this->payment_policy === self::PAYMENT_FULL_ADVANCE) {
            return $totalAmount;
        }

        if ($this->payment_policy === self::PAYMENT_PARTIAL_DEPOSIT && $this->deposit_percentage) {
            return $totalAmount * ($this->deposit_percentage / 100);
        }

        return 0; // Pay at checkout
    }
}
