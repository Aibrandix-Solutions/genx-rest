<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use App\Traits\HasBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        'nightly_rate_override',
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
        'nightly_rate_override' => 'decimal:2',
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

    public function restaurantSettlementPayments(): HasMany
    {
        return $this->hasMany(RestaurantSettlementPayment::class, 'reservation_id');
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
     * Nightly room rate for a stay date: reservation override, or room-type pricing.
     */
    public function getNightlyRateForDate($date): float
    {
        if ($this->nightly_rate_override !== null) {
            return (float) $this->nightly_rate_override;
        }

        $roomType = $this->room?->roomType;

        if (!$roomType) {
            return 0.0;
        }

        return (float) $roomType->getPriceForDate($date);
    }

    /**
     * Confirmed/checked-in stays that overlap a proposed window (half-open by datetime).
     * Back-to-back is allowed: existing checkout == new check-in is not a conflict.
     *
     * Uses date columns for index-friendly filtering, then precise time checks on
     * boundary days — avoids TIMESTAMP() wrapping that prevents index use.
     */
    public function scopeOverlappingStay(Builder $query, Carbon $checkIn, Carbon $checkOut): Builder
    {
        $inDate = $checkIn->toDateString();
        $outDate = $checkOut->toDateString();
        $inTime = $checkIn->format('H:i:s');
        $outTime = $checkOut->format('H:i:s');

        return $query
            ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_CHECKED_IN])
            // Coarse filter (uses check_in_date / checkout_date indexes)
            ->where('check_in_date', '<=', $outDate)
            ->where('checkout_date', '>=', $inDate)
            // Precise half-open: existing.start < new.end AND existing.end > new.start
            ->where(function (Builder $q) use ($outDate, $outTime) {
                $q->where('check_in_date', '<', $outDate)
                    ->orWhere(function (Builder $q2) use ($outDate, $outTime) {
                        $q2->where('check_in_date', '=', $outDate)
                            ->whereRaw("COALESCE(check_in_time, '00:00:00') < ?", [$outTime]);
                    });
            })
            ->where(function (Builder $q) use ($inDate, $inTime) {
                $q->where('checkout_date', '>', $inDate)
                    ->orWhere(function (Builder $q2) use ($inDate, $inTime) {
                        $q2->where('checkout_date', '=', $inDate)
                            ->whereRaw("COALESCE(checkout_time, '23:59:59') > ?", [$inTime]);
                    });
            });
    }

    /**
     * Build a stay datetime from date + optional time (H:i or H:i:s).
     */
    public static function combineDateAndTime($date, ?string $time, string $fallbackTime = '00:00:00'): Carbon
    {
        $dateString = $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();

        $normalized = self::normalizeTimeString($time) ?? self::normalizeTimeString($fallbackTime) ?? '00:00:00';

        return Carbon::parse($dateString . ' ' . $normalized);
    }

    public static function normalizeTimeString(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $time = trim($time);

        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sum of nightly room rates across the reservation stay (before tax/service).
     * Same-day (day-use) stays are charged one night.
     */
    public function calculateRoomChargesTotal(): float
    {
        $checkIn = $this->check_in_date->copy()->startOfDay();
        $checkOut = $this->checkout_date->copy()->startOfDay();

        $total = 0.0;
        $current = $checkIn->copy();

        if ($current->equalTo($checkOut)) {
            return round($this->getNightlyRateForDate($current), 2);
        }

        while ($current->lt($checkOut)) {
            $total += $this->getNightlyRateForDate($current);
            $current->addDay();
        }

        return round($total, 2);
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
     * Calculate total charges and update balance.
     *
     * Confirmed bookings may have an estimated stay total and advance payments
     * before room charges are posted at check-in. Do not wipe that estimate to 0
     * when the charges table is still empty (e.g. opening folio after booking payment).
     */
    public function calculateTotal()
    {
        $totalCharges = (float) $this->charges()->sum('amount');
        $totalPayments = (float) $this->payments()
            ->where('payment_type', '!=', HotelPayment::TYPE_REFUND)
            ->sum('amount');
        $totalRefunds = (float) $this->payments()
            ->where('payment_type', HotelPayment::TYPE_REFUND)
            ->sum('amount');

        $paidAmount = round($totalPayments - $totalRefunds, 2);

        $existingEstimate = (float) $this->total_amount;
        $effectiveTotal = $totalCharges;

        if ($totalCharges <= 0 && $this->status === self::STATUS_CONFIRMED) {
            if ($existingEstimate > 0) {
                $effectiveTotal = $existingEstimate;
            } else {
                // Recover totals wiped when folio ran calculateTotal() before check-in charges existed.
                $recovered = $this->calculateRoomChargesTotal();
                if ($recovered > 0) {
                    $effectiveTotal = $recovered;
                }
            }
        }

        $this->update([
            'total_amount' => $effectiveTotal,
            'paid_amount' => $paidAmount,
            'balance_due' => round($effectiveTotal - $paidAmount, 2),
        ]);
    }

    /**
     * Each calendar night billed for this stay (same-day / day-use = 1 night).
     *
     * @return array<int, Carbon>
     */
    public function getStayNightDates(): array
    {
        $checkIn = $this->check_in_date->copy()->startOfDay();
        $checkOut = $this->checkout_date->copy()->startOfDay();

        if ($checkIn->equalTo($checkOut)) {
            return [$checkIn->copy()];
        }

        $nights = [];
        $current = $checkIn->copy();

        while ($current->lt($checkOut)) {
            $nights[] = $current->copy();
            $current->addDay();
        }

        return $nights;
    }

    /**
     * Get number of billed nights (same-day stays count as 1).
     */
    public function getNumberOfNights(): int
    {
        return count($this->getStayNightDates());
    }

    /**
     * Count how many stay nights fall within an inclusive date period.
     */
    public function countStayNightsInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        $periodStart = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->startOfDay();

        return collect($this->getStayNightDates())->filter(
            fn (Carbon $night) => $night->gte($periodStart) && $night->lte($periodEnd)
        )->count();
    }

    /**
     * Estimated folio total before charges are posted (room + extra occupancy + tax + service).
     *
     * @return array{room: float, extra_occupancy: float, tax: float, service: float, total: float, nights: int}
     */
    public function estimateStayFolioTotal(?HotelSetting $settings = null): array
    {
        $this->loadMissing(['room.roomType']);
        $settings = $settings ?? HotelSetting::where('branch_id', $this->branch_id)->first();

        $roomTotal = $this->calculateRoomChargesTotal();
        $nights = $this->getNumberOfNights();
        $extraTotal = 0.0;

        if ($this->room?->roomType && $nights > 0) {
            $extraPerNight = $this->room->roomType->calculateExtraOccupancyCharges(
                (int) $this->adults,
                (int) $this->children,
                0
            );
            $extraTotal = round($extraPerNight * $nights, 2);
        }

        $taxableBase = $roomTotal + $extraTotal;
        $taxRate = $this->getEffectiveTaxRate();
        $taxAmount = round($taxableBase * ($taxRate / 100), 2);

        $serviceAmount = 0.0;
        if ($settings && (float) $settings->service_charge_rate > 0) {
            $serviceAmount = round(
                ($taxableBase + $taxAmount) * ((float) $settings->service_charge_rate / 100),
                2
            );
        }

        return [
            'room' => round($roomTotal, 2),
            'extra_occupancy' => $extraTotal,
            'tax' => $taxAmount,
            'service' => $serviceAmount,
            'total' => round($taxableBase + $taxAmount + $serviceAmount, 2),
            'nights' => $nights,
        ];
    }

    /**
     * Post room-night, extra occupancy, tax, and service charges (skips if room nights already exist).
     */
    public function postRoomNightCharges(?HotelSetting $settings = null): bool
    {
        return DB::transaction(function () use ($settings) {
            $reservation = static::query()->whereKey($this->id)->lockForUpdate()->first();

            if (! $reservation) {
                return false;
            }

            if ($reservation->charges()->where('charge_type', RoomCharge::TYPE_ROOM_NIGHT)->exists()) {
                return false;
            }

            $reservation->loadMissing(['room.roomType']);
            $room = $reservation->room;
            $roomType = $room?->roomType;

            if (! $room || ! $roomType) {
                return false;
            }

            $settings = $settings ?? HotelSetting::where('branch_id', $reservation->branch_id)->first();
            $checkIn = $reservation->check_in_date->copy();
            $roomChargesBase = 0.0;

            foreach ($reservation->getStayNightDates() as $nightDate) {
                $nightlyRate = $reservation->getNightlyRateForDate($nightDate);

                RoomCharge::create([
                    'branch_id' => $reservation->branch_id,
                    'reservation_id' => $reservation->id,
                    'charge_type' => RoomCharge::TYPE_ROOM_NIGHT,
                    'description' => 'Room ' . $room->room_number . ' - ' . $nightDate->format('d M Y'),
                    'amount' => $nightlyRate,
                    'charge_date' => $nightDate->toDateString(),
                ]);

                $roomChargesBase += (float) $nightlyRate;
            }

            $nights = $reservation->getNumberOfNights();
            $extraPerNight = $roomType->calculateExtraOccupancyCharges(
                (int) $reservation->adults,
                (int) $reservation->children,
                0
            );

            if ($extraPerNight > 0 && $nights > 0) {
                $extraTotal = round($extraPerNight * $nights, 2);

                RoomCharge::create([
                    'branch_id' => $reservation->branch_id,
                    'reservation_id' => $reservation->id,
                    'charge_type' => RoomCharge::TYPE_OTHER,
                    'description' => 'Extra occupancy charges (' . $nights . ' night' . ($nights === 1 ? '' : 's') . ')',
                    'amount' => $extraTotal,
                    'charge_date' => $checkIn->toDateString(),
                ]);

                $roomChargesBase += $extraTotal;
            }

            $taxRate = $reservation->getEffectiveTaxRate();
            $taxAmount = round($roomChargesBase * ($taxRate / 100), 2);

            if ($taxAmount > 0) {
                RoomCharge::create([
                    'branch_id' => $reservation->branch_id,
                    'reservation_id' => $reservation->id,
                    'charge_type' => RoomCharge::TYPE_TAX,
                    'description' => 'Tax (' . number_format($taxRate, 2, '.', '') . '%)',
                    'amount' => $taxAmount,
                    'charge_date' => $checkIn->toDateString(),
                ]);
            }

            if ($settings && (float) $settings->service_charge_rate > 0) {
                $serviceBase = $roomChargesBase + $taxAmount;
                $serviceAmount = round($serviceBase * ((float) $settings->service_charge_rate / 100), 2);

                if ($serviceAmount > 0) {
                    RoomCharge::create([
                        'branch_id' => $reservation->branch_id,
                        'reservation_id' => $reservation->id,
                        'charge_type' => RoomCharge::TYPE_SERVICE,
                        'description' => 'Service charge (' . number_format((float) $settings->service_charge_rate, 2, '.', '') . '%)',
                        'amount' => $serviceAmount,
                        'charge_date' => $checkIn->toDateString(),
                    ]);
                }
            }

            return true;
        });
    }
}
