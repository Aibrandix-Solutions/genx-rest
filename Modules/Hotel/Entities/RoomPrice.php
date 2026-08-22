<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPrice extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_room_prices';

    protected $fillable = [
        'branch_id',
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
