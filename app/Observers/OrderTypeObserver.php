<?php

namespace App\Observers;

use App\Models\OrderType;
use App\Services\PosBootstrapService;
use Illuminate\Support\Facades\Cache;

class OrderTypeObserver
{

    public function creating(OrderType $orderType)
    {
        if (branch() && $orderType->branch_id == null) {
            $orderType->branch_id = branch()->id;
        }
    }

    /**
     * Clear POS bootstrap cache when order type is created
     */
    public function created(OrderType $orderType)
    {
        $this->clearPosBootstrapCache($orderType->restaurant_id, $orderType->branch_id);
    }

    /**
     * Clear POS bootstrap cache when order type is updated
     */
    public function updated(OrderType $orderType)
    {
        $originalRestaurantId = $orderType->getOriginal('restaurant_id');
        $originalBranchId = $orderType->getOriginal('branch_id');

        if ($originalRestaurantId && (
            (int) $originalRestaurantId !== (int) $orderType->restaurant_id ||
            (string) ($originalBranchId ?? '0') !== (string) ($orderType->branch_id ?? '0')
        )) {
            $this->clearPosBootstrapCache($originalRestaurantId, $originalBranchId);
        }

        $this->clearPosBootstrapCache($orderType->restaurant_id, $orderType->branch_id);
    }

    /**
     * Clear POS bootstrap cache when order type is deleted
     */
    public function deleted(OrderType $orderType)
    {
        $this->clearPosBootstrapCache($orderType->restaurant_id, $orderType->branch_id);
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
