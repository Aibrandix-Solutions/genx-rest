<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Entities\InventoryMovement;

class InventoryMovementObserver
{

    public function creating(InventoryMovement $inventorymovement)
    {
        // DEBUG: Log observer activity
        \Log::info('[TRANSFER DEBUG] InventoryMovementObserver::creating called', [
            'existing_branch_id' => $inventorymovement->branch_id,
            'current_branch_id' => branch()?->id,
            'transaction_type' => $inventorymovement->transaction_type,
            'transfer_branch_id' => $inventorymovement->transfer_branch_id,
        ]);
        
        if (branch()) {
            // Only set branch_id if it's not already set (to allow destination transfers)
            if (!$inventorymovement->branch_id) {
                $inventorymovement->branch_id = branch()->id;
                \Log::info('[TRANSFER DEBUG] Observer set branch_id', ['branch_id' => $inventorymovement->branch_id]);
            } else {
                \Log::info('[TRANSFER DEBUG] Observer skipped setting branch_id (already set)', ['branch_id' => $inventorymovement->branch_id]);
            }
        }

        if (user() && !$inventorymovement->added_by) {
            $inventorymovement->added_by = user()->id;
        }
    }

}
