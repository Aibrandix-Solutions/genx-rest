<?php

namespace Modules\Inventory\Observers;

use App\Models\Branch;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Entities\InventoryItemCategory;

class BranchObserver
{

    public function created(Branch $branch): void
    {

        // Units are now restaurant-scoped (not branch-scoped)
        // Only create them once globally if they don't exist
        foreach (Unit::UNITS as $unit) {
            Unit::firstOrCreate($unit);
        }

        // Categories are now restaurant-scoped (not branch-scoped)
        // Only create them once globally if they don't exist
        foreach (InventoryItemCategory::CATEGORIES as $category) {
            InventoryItemCategory::firstOrCreate([
                'name' => $category
            ]);
        }
    }
}
