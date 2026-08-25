<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hotel\Entities\HotelSetting;

class RoomType extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'branch_id',
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
     * Get price for specific date (considering dynamic pricing).
     * Uses eager-loaded prices when available to avoid N+1 queries.
     */
    public function getPriceForDate($date, $respectDynamicPricingSetting = true, ?HotelSetting $setting = null)
    {
        if ($respectDynamicPricingSetting) {
            $setting = $setting ?? HotelSetting::query()
                ->where('branch_id', $this->branch_id)
                ->first();

            if ($setting && ! $setting->enable_dynamic_pricing) {
                return $this->base_price;
            }
        }

        $dateString = $date instanceof \Carbon\Carbon
            ? $date->toDateString()
            : \Carbon\Carbon::parse($date)->toDateString();

        if ($this->relationLoaded('prices')) {
            $dynamicPrice = $this->prices->first(function ($price) use ($dateString) {
                $from = $price->date_from instanceof \Carbon\Carbon
                    ? $price->date_from->toDateString()
                    : (string) $price->date_from;
                $to = $price->date_to instanceof \Carbon\Carbon
                    ? $price->date_to->toDateString()
                    : (string) $price->date_to;

                return $from <= $dateString && $to >= $dateString;
            });

            return $dynamicPrice ? $dynamicPrice->price : $this->base_price;
        }

        $dynamicPrice = $this->prices()
            ->whereDate('date_from', '<=', $dateString)
            ->whereDate('date_to', '>=', $dateString)
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
