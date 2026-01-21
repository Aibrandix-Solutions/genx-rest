<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Entities\InventoryItemCategory;

class InventoryItemCategoryObserver
{


    public function creating(InventoryItemCategory $inventoryitemcategory)
    {
        if (restaurant() && empty($inventoryitemcategory->restaurant_id)) {
            $inventoryitemcategory->restaurant_id = restaurant()->id;
        }
    }
}
