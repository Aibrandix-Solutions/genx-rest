<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'branch_id',
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
     * Check if room is available for a stay window (date or datetime).
     * Uses datetime half-open overlap so same-day back-to-back bookings are allowed.
     */
    public function isAvailableForDates($checkIn, $checkOut)
    {
        $checkInAt = $checkIn instanceof \Carbon\Carbon
            ? $checkIn->copy()
            : \Carbon\Carbon::parse($checkIn);
        $checkOutAt = $checkOut instanceof \Carbon\Carbon
            ? $checkOut->copy()
            : \Carbon\Carbon::parse($checkOut);

        if (in_array($this->status, [self::STATUS_MAINTENANCE, self::STATUS_BLOCKED], true)) {
            return false;
        }

        $conflictingReservations = $this->reservations()
            ->overlappingStay($checkInAt, $checkOutAt)
            ->exists();

        return ! $conflictingReservations;
    }
}
