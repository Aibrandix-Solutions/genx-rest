<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_reservations';

    protected $fillable = [
        'branch_id',
        'restaurant_id',
        'guest_id',
        'room_id',
        'reservation_number',
        'check_in_date',
        'check_in_time',
        'checkout_date',
        'checkout_time',
        'actual_check_in',
        'actual_checkout',
        'adults',
        'children',
        'special_requests',
        'booking_source',
        'status',
        'total_amount',
        'paid_amount',
        'balance_due',
        'tax_rate_override',
        'group_booking_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'checkout_date' => 'date',
        'actual_check_in' => 'datetime',
        'actual_checkout' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'tax_rate_override' => 'decimal:2',
    ];

    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_CHECKED_OUT = 'checked_out';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (!$reservation->branch_id && branch()) {
                $reservation->branch_id = branch()->id;
            }
            if (!$reservation->restaurant_id && restaurant()) {
                $reservation->restaurant_id = restaurant()->id;
            }
            if (!$reservation->reservation_number) {
                $reservation->reservation_number = self::generateReservationNumber();
            }
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(RoomCharge::class);
    }

    /**
     * Get all reservations in the same group booking
     */
    public function groupedReservations()
    {
        if (!$this->group_booking_id) {
            return collect([$this]);
        }

        return static::where('group_booking_id', $this->group_booking_id)->get();
    }

    /**
     * Generate a unique group booking ID
     */
    public static function generateGroupBookingId(): string
    {
        do {
            $id = 'GRP' . now()->format('Ymd') . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('group_booking_id', $id)->exists());

        return $id;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Models\Order::class, 'hotel_reservation_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HotelPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function hotelSettings(): BelongsTo
    {
        return $this->belongsTo(HotelSetting::class, 'branch_id', 'branch_id');
    }

    /**
     * Generate unique reservation number
     */
    public static function generateReservationNumber(): string
    {
        $prefix = 'RES';
        $date = now()->format('Ymd');
        $pattern = $prefix . $date . '%';

        do {
            $lastNumber = self::where('reservation_number', 'like', $pattern)
                ->orderByRaw('CAST(SUBSTRING(reservation_number, -4) AS UNSIGNED) DESC')
                ->value('reservation_number');

            $sequence = $lastNumber ? (int) substr($lastNumber, -4) + 1 : 1;
            $candidate = $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        } while (self::where('reservation_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Tax rate for this reservation: folio override, or hotel settings default.
     */
    public function getEffectiveTaxRate(): float
    {
        if ($this->tax_rate_override !== null) {
            return (float) $this->tax_rate_override;
        }

        $settings = HotelSetting::where('branch_id', $this->branch_id)->first();

        return $settings ? (float) $settings->tax_rate : 0.0;
    }

    /**
     * Room-night charges used as the tax calculation base.
     */
    public function getTaxableRoomChargesBase(): float
    {
        return (float) $this->charges()
            ->whereIn('charge_type', [RoomCharge::TYPE_ROOM_NIGHT, RoomCharge::TYPE_OTHER])
            ->sum('amount');
    }

    /**
     * Create or update the auto-generated tax charge for this reservation.
     */
    public function recalculateTaxCharge(): void
    {
        $taxRate = $this->getEffectiveTaxRate();
        $base = $this->getTaxableRoomChargesBase();
        $taxCharge = $this->charges()->where('charge_type', RoomCharge::TYPE_TAX)->first();

        if ($taxRate <= 0 || $base <= 0) {
            if ($taxCharge) {
                $taxCharge->delete();
            }

            return;
        }

        $taxAmount = round($base * ($taxRate / 100), 2);
        $description = 'Tax (' . number_format($taxRate, 2, '.', '') . '%)';

        if ($taxCharge) {
            $taxCharge->update([
                'amount' => $taxAmount,
                'description' => $description,
            ]);

            return;
        }

        RoomCharge::create([
            'branch_id'      => $this->branch_id,
            'reservation_id' => $this->id,
            'charge_type' => RoomCharge::TYPE_TAX,
            'description' => $description,
            'amount' => $taxAmount,
            'charge_date' => $this->check_in_date->toDateString(),
        ]);
    }

    /**
     * Recalculate auto-generated service charge when tax changes (folio flow includes tax in base).
     */
    public function recalculateLinkedServiceCharge(): void
    {
        $settings = HotelSetting::where('branch_id', $this->branch_id)->first();

        if (!$settings || (float) $settings->service_charge_rate <= 0) {
            return;
        }

        $serviceCharge = $this->charges()
            ->where('charge_type', RoomCharge::TYPE_SERVICE)
            ->where('description', 'like', 'Service charge (%')
            ->first();

        if (!$serviceCharge) {
            return;
        }

        $base = $this->getTaxableRoomChargesBase();
        $taxAmount = (float) $this->charges()->where('charge_type', RoomCharge::TYPE_TAX)->sum('amount');
        $serviceAmount = round(($base + $taxAmount) * ((float) $settings->service_charge_rate / 100), 2);

        $serviceCharge->update([
            'amount' => $serviceAmount,
            'description' => 'Service charge (' . number_format((float) $settings->service_charge_rate, 2, '.', '') . '%)',
        ]);
    }

    /**
     * Keep tax rate + description aligned when staff edit the tax amount inline.
     */
    public function syncTaxRateFromAmount(float $taxAmount): void
    {
        $base = $this->getTaxableRoomChargesBase();

        if ($base <= 0) {
            return;
        }

        $impliedRate = round(($taxAmount / $base) * 100, 2);
        $settings = HotelSetting::where('branch_id', $this->branch_id)->first();
        $defaultRate = $settings ? (float) $settings->tax_rate : 0.0;

        $this->update([
            'tax_rate_override' => $impliedRate === $defaultRate ? null : $impliedRate,
        ]);

        $taxCharge = $this->charges()->where('charge_type', RoomCharge::TYPE_TAX)->first();

        if ($taxCharge) {
            $taxCharge->update([
                'amount' => round($taxAmount, 2),
                'description' => 'Tax (' . number_format($impliedRate, 2, '.', '') . '%)',
            ]);
        }
    }

    /**
     * Calculate total charges and update balance
     */
    public function calculateTotal()
    {
        $totalCharges = $this->charges()->sum('amount');
        $totalPayments = $this->payments()
            ->where('payment_type', '!=', HotelPayment::TYPE_REFUND)
            ->sum('amount');
        $totalRefunds = $this->payments()
            ->where('payment_type', HotelPayment::TYPE_REFUND)
            ->sum('amount');

        $paidAmount = $totalPayments - $totalRefunds;

        $this->update([
            'total_amount' => $totalCharges,
            'paid_amount' => $paidAmount,
            'balance_due' => $totalCharges - $paidAmount,
        ]);
    }

    /**
     * Get number of nights
     */
    public function getNumberOfNights()
    {
        return $this->check_in_date->diffInDays($this->checkout_date);
    }
}
