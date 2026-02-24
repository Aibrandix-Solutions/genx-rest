<?php

namespace Modules\Hotel\Entities;

use App\Models\User;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory, HasRestaurant;

    protected $table = 'hotel_reservations';

    protected $fillable = [
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
        return 'GRP' . now()->format('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
        return $this->belongsTo(HotelSetting::class, 'restaurant_id', 'restaurant_id');
    }

    /**
     * Generate unique reservation number
     */
    public static function generateReservationNumber()
    {
        $prefix = 'RES';
        $date = now()->format('Ymd');
        $pattern = $prefix . $date . '%';

        $lastNumber = self::where('reservation_number', 'like', $pattern)
            ->orderByRaw("CAST(SUBSTRING(reservation_number, -4) AS UNSIGNED) DESC")
            ->value('reservation_number');

        $sequence = $lastNumber ? (int) substr($lastNumber, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
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
