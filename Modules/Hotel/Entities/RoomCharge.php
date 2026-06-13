<?php

namespace Modules\Hotel\Entities;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomCharge extends Model
{
    use HasFactory;

    protected $table = 'hotel_room_charges';

    protected $fillable = [
        'reservation_id',
        'charge_type',
        'order_id',
        'description',
        'amount',
        'charge_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_date' => 'datetime',
    ];

    const TYPE_ROOM_NIGHT = 'room_night';
    const TYPE_RESTAURANT = 'restaurant';
    const TYPE_MINIBAR = 'minibar';
    const TYPE_LAUNDRY = 'laundry';
    const TYPE_SERVICE = 'service';
    const TYPE_TAX = 'tax';
    const TYPE_OTHER = 'other';

    /**
     * Custom charge types (charge_type = other) are stored as "Label::description".
     */
    public function getCustomTypeLabel(): ?string
    {
        if ($this->charge_type !== self::TYPE_OTHER) {
            return null;
        }

        $parts = explode('::', $this->description, 2);

        if (count($parts) === 2 && trim($parts[0]) !== '') {
            return trim($parts[0]);
        }

        return null;
    }

    public function getDisplayDescription(): string
    {
        if ($this->getCustomTypeLabel() !== null) {
            $parts = explode('::', $this->description, 2);

            return trim($parts[1] ?? '') ?: $this->getCustomTypeLabel();
        }

        return $this->description;
    }

    public static function encodeCustomTypeDescription(string $customType, string $description): string
    {
        $customType = trim($customType);
        $description = trim($description);

        return $description === '' ? $customType : $customType . '::' . $description;
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
