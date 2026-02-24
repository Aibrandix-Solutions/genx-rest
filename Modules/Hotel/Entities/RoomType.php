<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory, HasRestaurant;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'restaurant_id',
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
    public function getPriceForDate($date, $respectDynamicPricingSetting = true)
    {
        // Check if dynamic pricing is enabled from settings
        if ($respectDynamicPricingSetting) {
            $setting = $this->restaurant()->first()?->hotelSettings ?? null;
            if ($setting && !$setting->enable_dynamic_pricing) {
                return $this->base_price;
            }
        }

        $dynamicPrice = $this->prices()
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->first();

        return $dynamicPrice ? $dynamicPrice->price : $this->base_price;
    }

    /**
     * Get extra bed charge
     */
    public function getExtraBedCharge()
    {
        return $this->extra_bed_charge ?? 0;
    }

    /**
     * Get extra person charge
     */
    public function getExtraPersonCharge()
    {
        return $this->extra_person_charge ?? 0;
    }

    /**
     * Calculate extra occupancy charges
     */
    public function calculateExtraOccupancyCharges($adults, $children, $extraBedsUsed = 0)
    {
        $totalCharge = 0;

        // Extra person charge (beyond max occupancy)
        $totalGuests = $adults + $children;
        if ($totalGuests > $this->max_occupancy) {
            $extraPersons = $totalGuests - $this->max_occupancy;
            $totalCharge += $extraPersons * $this->getExtraPersonCharge();
        }

        // Extra bed charge (if applicable)
        if ($extraBedsUsed > 0) {
            $totalCharge += $extraBedsUsed * $this->getExtraBedCharge();
        }

        return $totalCharge;
    }
}
