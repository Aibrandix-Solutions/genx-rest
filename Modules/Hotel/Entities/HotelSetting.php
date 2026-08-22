<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelSetting extends Model
{
    use HasFactory, HasBranch;

    const MODE_HOTEL_PRIMARY = 'hotel_primary';
    const MODE_RESTAURANT_PRIMARY = 'restaurant_primary';
    const MODE_EQUAL = 'equal';

    protected $fillable = [
        'branch_id',
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
        'enable_payment_surcharge',
        'payment_surcharge_rate',
    ];

    protected $casts = [
        'business_mode' => 'string',
        'early_checkin_charge_per_hour' => 'decimal:2',
        'late_checkout_charge_per_hour' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'enable_room_service' => 'boolean',
        'enable_housekeeping_module' => 'boolean',
        'enable_dynamic_pricing' => 'boolean',
        'enable_payment_surcharge' => 'boolean',
        'max_rooms_per_booking' => 'integer',
        'tax_rate' => 'decimal:2',
        'service_charge_rate' => 'decimal:2',
        'payment_surcharge_rate' => 'decimal:2',
    ];

    const PAYMENT_FULL_ADVANCE = 'full_advance';
    const PAYMENT_PARTIAL_DEPOSIT = 'partial_deposit';
    const PAYMENT_AT_CHECKOUT = 'pay_at_checkout';


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

    /**
     * Calculate tax on a given amount
     */
    public function calculateTax($amount)
    {
        if (!$this->tax_rate) {
            return 0;
        }
        return $amount * ($this->tax_rate / 100);
    }

    /**
     * Calculate service charge on a given amount
     */
    public function calculateServiceCharge($amount)
    {
        if (!$this->service_charge_rate) {
            return 0;
        }
        return $amount * ($this->service_charge_rate / 100);
    }

    /**
     * Get early check-in charge per hour
     */
    public function getEarlyCheckInCharge()
    {
        return $this->early_checkin_charge_per_hour ?? 0;
    }

    /**
     * Get late checkout charge per hour
     */
    public function getLateCheckoutCharge()
    {
        return $this->late_checkout_charge_per_hour ?? 0;
    }

    public function paymentSurchargeAppliesTo(string $method): bool
    {
        return $this->enable_payment_surcharge
            && (float) $this->payment_surcharge_rate > 0
            && in_array($method, ['card', 'bank_transfer'], true);
    }

    public function calculatePaymentSurcharge(float $amount, string $method): float
    {
        if ($amount <= 0 || !$this->paymentSurchargeAppliesTo($method)) {
            return 0;
        }

        return round($amount * ((float) $this->payment_surcharge_rate / 100), 2);
    }

    public function paymentSurchargeDescription(string $method): string
    {
        $label = match ($method) {
            'card' => 'Card',
            'bank_transfer' => 'Bank transfer',
            default => ucfirst(str_replace('_', ' ', $method)),
        };

        return $label . ' payment surcharge (' . number_format((float) $this->payment_surcharge_rate, 2, '.', '') . '%)';
    }
}
