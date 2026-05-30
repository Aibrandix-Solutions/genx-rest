<?php

namespace App\Observers;

use App\Models\Tax;
use App\Services\PosBootstrapService;
use Illuminate\Support\Facades\Cache;

class TaxObserver
{

    public function creating(Tax $tax)
    {
        if (restaurant()) {
            $tax->restaurant_id = restaurant()->id;
        }
    }

    /**
     * Clear POS bootstrap cache when tax is created
     */
    public function created(Tax $tax)
    {
        $this->clearPosBootstrapCache($tax->restaurant_id);
    }

    /**
     * Clear POS bootstrap cache when tax is updated
     */
    public function updated(Tax $tax)
    {
        $originalRestaurantId = $tax->getOriginal('restaurant_id');

        if ($originalRestaurantId && (int) $originalRestaurantId !== (int) $tax->restaurant_id) {
            $this->clearPosBootstrapCache($originalRestaurantId);
        }

        $this->clearPosBootstrapCache($tax->restaurant_id);
    }

    /**
     * Clear POS bootstrap cache when tax is deleted
     */
    public function deleted(Tax $tax)
    {
        $this->clearPosBootstrapCache($tax->restaurant_id);
    }

    /**
     * Clear POS bootstrap cache for all branches of given restaurant
     */
    private function clearPosBootstrapCache($restaurantId)
    {
        // Invalidate cache for all branches
        $branches = \App\Models\Branch::where('restaurant_id', $restaurantId)->get();
        foreach ($branches as $branch) {
            app(PosBootstrapService::class)->clearCache((int) $restaurantId, (int) $branch->id);
        }
    }

}
