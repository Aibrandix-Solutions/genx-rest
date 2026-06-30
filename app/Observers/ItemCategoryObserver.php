<?php

namespace App\Observers;

use App\Models\ItemCategory;
use App\Services\PosBootstrapService;
use Illuminate\Support\Facades\Cache;

class ItemCategoryObserver
{

    public function creating(ItemCategory $itemCategory)
    {
        if (branch()) {
            $itemCategory->branch_id = branch()->id;
        }
    }

    /**
     * Clear POS bootstrap cache when category is created
     */
    public function created(ItemCategory $itemCategory)
    {
        $this->clearPosBootstrapCache($itemCategory);
    }

    /**
     * Clear POS bootstrap cache when category is updated
     */
    public function updated(ItemCategory $itemCategory)
    {
        $this->clearPosBootstrapCache($itemCategory);
    }

    /**
     * Clear POS bootstrap cache when category is deleted
     */
    public function deleted(ItemCategory $itemCategory)
    {
        $this->clearPosBootstrapCache($itemCategory);
    }

    /**
     * Clear POS bootstrap cache for given restaurant/branch
     */
    private function clearPosBootstrapCache(ItemCategory $itemCategory): void
    {
        $branchId = $itemCategory->branch_id !== null ? (int) $itemCategory->branch_id : null;

        // item_categories does not carry restaurant_id in this app schema.
        // Resolve restaurant via branch so cache invalidation always runs.
        $restaurantId = null;
        if ($branchId !== null) {
            $restaurantId = \App\Models\Branch::query()
                ->withoutGlobalScopes()
                ->whereKey($branchId)
                ->value('restaurant_id');
        }

        if (!$restaurantId) {
            try {
                $restaurantId = restaurant()?->id;
            } catch (\Throwable) {
                $restaurantId = null;
            }
        }

        if (!$restaurantId) {
            return;
        }

        app(PosBootstrapService::class)->clearCache((int) $restaurantId, $branchId);
    }

}
