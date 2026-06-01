<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, HasRestaurant;

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'restaurant_id',
        'room_type_id',
        'room_number',
        'floor',
        'section',
        'status',
        'notes',
        'last_cleaned_at',
    ];

    protected $casts = [
        'last_cleaned_at' => 'datetime',
    ];

    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_CLEANING = 'cleaning';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_RESERVED = 'reserved';
    const STATUS_BLOCKED = 'blocked';

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    /**
     * Get current reservation (checked-in)
     */
    public function currentReservation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Reservation::class)->where('status', Reservation::STATUS_CHECKED_IN);
    }

    /**
     * Check if room is available for specific dates
     */
    public function isAvailableForDates($checkIn, $checkOut)
    {
        // Half-open interval: conflict exists when existing.check_in < new.checkout
        // AND existing.checkout > new.check_in
        // Allows same-day turnover (checkout Jan 5 = available for Jan 5 check-in)
        $conflictingReservations = $this->reservations()
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
            ->where('check_in_date', '<', $checkOut)
            ->where('checkout_date', '>', $checkIn)
            ->exists();

        return !$conflictingReservations && in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_RESERVED]);
    }
}
