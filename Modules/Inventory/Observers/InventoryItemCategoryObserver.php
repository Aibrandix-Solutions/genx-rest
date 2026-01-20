<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Entities\InventoryItemCategory;

class InventoryItemCategoryObserver
{


    public function creating(InventoryItemCategory $inventoryitemcategory)
    {
        // Disabled: Categories are now restaurant-scoped, not branch-scoped
        // if (branch()) {
        //     $inventoryitemcategory->branch_id = branch()->id;
        // }
    }
}
