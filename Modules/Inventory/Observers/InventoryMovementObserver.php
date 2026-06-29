<?php

namespace Modules\Inventory\Observers;

use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;
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

    public function created(InventoryMovement $inventoryMovement): void
    {
        $inventoryMovement->loadMissing('item:id,name');

        $itemName = $inventoryMovement->item?->name ?? 'item';

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::InventoryMovementCreated,
            description: sprintf(
                'Inventory %s: %s x %s',
                $inventoryMovement->transaction_type,
                $inventoryMovement->quantity,
                $itemName
            ),
            properties: [
                'inventory_movement_id' => $inventoryMovement->id,
                'inventory_item_id' => $inventoryMovement->inventory_item_id,
                'item_name' => $itemName,
                'quantity' => $inventoryMovement->quantity,
                'transaction_type' => $inventoryMovement->transaction_type,
                'waste_reason' => $inventoryMovement->waste_reason,
                'inventory_transfer_id' => $inventoryMovement->inventory_transfer_id,
            ],
            branchId: $inventoryMovement->branch_id ? (int) $inventoryMovement->branch_id : null,
            legacySource: 'inventory_movements',
            legacyId: (int) $inventoryMovement->id,
            createdAt: $inventoryMovement->created_at,
        );
    }

}
