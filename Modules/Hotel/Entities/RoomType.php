<?php

namespace Modules\Hotel\Entities;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'base_price',
        'max_occupancy',
        'extra_bed_charge',
        'extra_person_charge',
        'amenities',
        'photos',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'extra_bed_charge' => 'decimal:2',
        'extra_person_charge' => 'decimal:2',
        'amenities' => 'array',
        'photos' => 'array',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(RoomPrice::class);
    }

    /**
     * Get price for specific date (considering dynamic pricing)
     */
    public function getPriceForDate($date)
    {
        $dynamicPrice = $this->prices()
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->first();

        return $dynamicPrice ? $dynamicPrice->price : $this->base_price;
    }
}
