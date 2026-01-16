<?php

namespace Modules\Inventory\Livewire\StockTransfer;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\InventoryMovement;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class ReceiveStockTransfer extends Component
{
    use LivewireAlert;

    public $transfer;
    public $receivedItems = [];
    public $showModal = false;

    protected $listeners = [
        'openReceiveTransferModal' => 'openModal',
    ];

    public function mount($transfer = null)
    {
        if ($transfer) {
            $this->transfer = $transfer;
            $this->loadReceivedItems();
        }
    }

    public function loadReceivedItems()
    {
        if (!$this->transfer) return;

        foreach ($this->transfer->items as $item) {
            $this->receivedItems[$item->id] = [
                'requested_quantity' => $item->requested_quantity,
                'confirmed_quantity' => $item->confirmed_quantity ?? $item->requested_quantity,
                'notes' => $item->notes ?? '',
            ];
        }
    }

    public function openModal($transferId)
    {
        $this->transfer = InventoryTransfer::with([
            'items.sourceItem.unit',
            'items.destinationItem.unit',
            'sourceLocation',
            'destinationLocation',
        ])->findOrFail($transferId);
        
        // Restaurant-scoped: any user can receive transfers

        $this->loadReceivedItems();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->transfer = null;
        $this->receivedItems = [];
        $this->dispatch('closeReceiveModal');
    }

    public function rules()
    {
        $rules = [];
        
        foreach ($this->receivedItems as $itemId => $data) {
            $rules["receivedItems.{$itemId}.confirmed_quantity"] = "required|numeric|min:0|max:{$data['requested_quantity']}";
        }
        
        return $rules;
    }

    public function messages()
    {
        $messages = [];
        
        if (!$this->transfer) {
            return $messages;
        }
        
        foreach ($this->receivedItems as $itemId => $data) {
            $item = $this->transfer->items->find($itemId);
            $itemName = __('inventory::modules.transfers.item');
            
            if ($item && $item->destinationItem) {
                $itemName = $item->destinationItem->name;
            }
            
            $maxQty = $data['requested_quantity'] ?? 0;
            
            $messages["receivedItems.{$itemId}.confirmed_quantity.required"] = __('inventory::modules.transfers.confirmed_quantity_required', ['item' => $itemName]);
            $messages["receivedItems.{$itemId}.confirmed_quantity.numeric"] = __('inventory::modules.transfers.confirmed_quantity_numeric', ['item' => $itemName]);
            $messages["receivedItems.{$itemId}.confirmed_quantity.min"] = __('inventory::modules.transfers.confirmed_quantity_min', ['item' => $itemName]);
            $messages["receivedItems.{$itemId}.confirmed_quantity.max"] = __('inventory::modules.transfers.confirmed_quantity_max', ['item' => $itemName, 'max' => $maxQty]);
        }
        
        return $messages;
    }

    public function confirmReceive()
    {
        if (!$this->transfer) return;

        $this->validate();

        try {
            DB::transaction(function () {
                $allCompleted = true;
                $allPartial = true;

                foreach ($this->transfer->items as $item) {
                    if (!isset($this->receivedItems[$item->id])) {
                        continue; // Skip items not in received items array
                    }
                    
                    $receivedData = $this->receivedItems[$item->id];
                    $confirmedQty = $receivedData['confirmed_quantity'] ?? 0;
                    
                    if (!is_numeric($confirmedQty)) {
                        $confirmedQty = 0;
                    }

                    if ($confirmedQty <= 0) {
                        continue; // Skip items with zero quantity
                    }

                    // Update transfer item
                    $item->confirmed_quantity = $confirmedQty;
                    $item->notes = $receivedData['notes'] ?? null;

                    if ($confirmedQty >= $item->requested_quantity) {
                        $item->status = 'completed';
                    } elseif ($confirmedQty > 0) {
                        $item->status = 'partially_received';
                        $allCompleted = false;
                    } else {
                        $item->status = 'pending';
                        $allCompleted = false;
                        $allPartial = false;
                    }

                    $item->save();

                    // Update inventory stock at destination location
                    if ($this->transfer->destination_location_id) {
                        $stock = InventoryStock::where('inventory_item_id', $item->destination_inventory_item_id)
                            ->where('location_id', $this->transfer->destination_location_id)
                            ->firstOrCreate([
                                'inventory_item_id' => $item->destination_inventory_item_id,
                                'branch_id' => branch()->id,
                                'location_id' => $this->transfer->destination_location_id,
                            ], [
                                'quantity' => 0
                            ]);
                    } else {
                        // Fallback to branch-based stock for old transfers
                        $stock = InventoryStock::where('inventory_item_id', $item->destination_inventory_item_id)
                            ->where('branch_id', branch()->id)
                            ->firstOrCreate([
                                'inventory_item_id' => $item->destination_inventory_item_id,
                                'branch_id' => branch()->id,
                            ], [
                                'quantity' => 0
                            ]);
                    }

                    $stock->quantity += $confirmedQty;
                    $stock->save();

                    // Find and update destination movement using transfer linking
                    $movement = InventoryMovement::where('branch_id', branch()->id)
                        ->where('inventory_item_id', $item->destination_inventory_item_id)
                        ->where('transaction_type', 'in')
                        ->where('inventory_transfer_id', $this->transfer->id)
                        ->where('inventory_transfer_item_id', $item->id)
                        ->first();

                    if ($movement) {
                        // Update movement quantity if different
                        if ($movement->quantity != $confirmedQty) {
                            $movement->quantity = $confirmedQty;
                            $movement->save();
                        }
                        
                        // Ensure unit price is set (use source item's price for cost tracking)
                        if (!$movement->unit_purchase_price) {
                            $sourceItem = InventoryItem::withoutGlobalScopes()->find($item->source_inventory_item_id);
                            if ($sourceItem && $sourceItem->unit_purchase_price) {
                                $movement->unit_purchase_price = $sourceItem->unit_purchase_price;
                                $movement->save();
                            }
                        }
                    } else {
                        // Fallback: Try finding by transfer_branch_id (for backwards compatibility)
                        $movement = InventoryMovement::where('branch_id', branch()->id)
                            ->where('inventory_item_id', $item->destination_inventory_item_id)
                            ->where('transaction_type', 'in')
                            ->where('transfer_branch_id', $this->transfer->source_branch_id)
                            ->where('quantity', $item->requested_quantity)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        
                        if ($movement) {
                            // Link it to the transfer for future reference
                            $movement->inventory_transfer_id = $this->transfer->id;
                            $movement->inventory_transfer_item_id = $item->id;
                            if ($movement->quantity != $confirmedQty) {
                                $movement->quantity = $confirmedQty;
                            }
                            
                            // Set unit price from source item if not set
                            if (!$movement->unit_purchase_price) {
                                $sourceItem = InventoryItem::withoutGlobalScopes()->find($item->source_inventory_item_id);
                                if ($sourceItem && $sourceItem->unit_purchase_price) {
                                    $movement->unit_purchase_price = $sourceItem->unit_purchase_price;
                                }
                            }
                            
                            $movement->save();
                        }
                    }

                    // Update menu item status
                    if ($item->destinationItem) {
                        $item->destinationItem->menuItems()->update([
                            'in_stock' => 1
                        ]);
                    }
                }

                // Update transfer status based on item statuses
                $itemStatuses = $this->transfer->items()->pluck('status')->toArray();
                $hasCompleted = in_array('completed', $itemStatuses);
                $hasPartiallyReceived = in_array('partially_received', $itemStatuses);
                $hasPending = in_array('pending', $itemStatuses);
                $hasInTransit = in_array('in_transit', $itemStatuses);
                
                if ($allCompleted && !$hasPending && !$hasPartiallyReceived && !$hasInTransit) {
                    // All items completed - transfer is completed
                    $this->transfer->status = 'completed';
                } elseif ($hasCompleted || $hasPartiallyReceived) {
                    // Some items completed or partially received - keep as in_transit
                    // This allows for additional receives if needed
                    $this->transfer->status = 'in_transit';
                } else {
                    // No items received yet - keep as pending
                    $this->transfer->status = 'pending';
                }

                $this->transfer->confirmed_by = user()->id;
                $this->transfer->confirmed_at = now();
                $this->transfer->save();
            });

            $this->alert('success', __('inventory::modules.transfers.transfer_confirmed_successfully'));
            $this->dispatch('transferReceived');
            $this->dispatch('closeReceiveModal');
            
        } catch (\Exception $e) {
            $this->alert('error', __('inventory::modules.transfers.transfer_confirmation_failed') . ': ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('inventory::livewire.stock-transfer.receive-stock-transfer');
    }
}

