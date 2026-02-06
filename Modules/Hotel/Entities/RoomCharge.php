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
        'charge_date' => 'date',
    ];

    const TYPE_ROOM_NIGHT = 'room_night';
    const TYPE_RESTAURANT = 'restaurant';
    const TYPE_MINIBAR = 'minibar';
    const TYPE_LAUNDRY = 'laundry';
    const TYPE_SERVICE = 'service';
    const TYPE_TAX = 'tax';
    const TYPE_OTHER = 'other';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
