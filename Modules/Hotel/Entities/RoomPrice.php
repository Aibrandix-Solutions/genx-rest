<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPrice extends Model
{
    use HasFactory, HasRestaurant;

    protected $table = 'hotel_room_prices';

    protected $fillable = [
        'restaurant_id',
        'room_type_id',
        'date_from',
        'date_to',
        'price',
        'reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'price' => 'decimal:2',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
