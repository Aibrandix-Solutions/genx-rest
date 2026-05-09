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
        $this->clearPosBootstrapCache($itemCategory->restaurant_id, $itemCategory->branch_id);
    }

    /**
     * Clear POS bootstrap cache when category is updated
     */
    public function updated(ItemCategory $itemCategory)
    {
        $originalRestaurantId = $itemCategory->getOriginal('restaurant_id');
        $originalBranchId = $itemCategory->getOriginal('branch_id');

        if ($originalRestaurantId && (
            (int) $originalRestaurantId !== (int) $itemCategory->restaurant_id ||
            (string) ($originalBranchId ?? '0') !== (string) ($itemCategory->branch_id ?? '0')
        )) {
            $this->clearPosBootstrapCache($originalRestaurantId, $originalBranchId);
        }

        $this->clearPosBootstrapCache($itemCategory->restaurant_id, $itemCategory->branch_id);
    }

    /**
     * Clear POS bootstrap cache when category is deleted
     */
    public function deleted(ItemCategory $itemCategory)
    {
        $this->clearPosBootstrapCache($itemCategory->restaurant_id, $itemCategory->branch_id);
    }

    /**
     * Clear POS bootstrap cache for given restaurant/branch
     */
    private function clearPosBootstrapCache($restaurantId, $branchId)
    {
        if (!$restaurantId) {
            return;
        }

        app(PosBootstrapService::class)->clearCache((int) $restaurantId, $branchId !== null ? (int) $branchId : null);
    }

}
