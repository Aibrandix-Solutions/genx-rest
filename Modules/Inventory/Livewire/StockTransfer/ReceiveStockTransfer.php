<?php

namespace Modules\Inventory\Livewire\StockTransfer;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\InventoryMovement;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $alreadyConfirmed = (float)($item->confirmed_quantity ?? 0);
            $requested        = (float)$item->requested_quantity;
            $remaining        = max(0, $requested - $alreadyConfirmed);

            // Skip items that are already fully received
            if ($item->status === 'completed') {
                continue;
            }

            $this->receivedItems[$item->id] = [
                'requested_quantity'  => $requested,
                'already_confirmed'   => $alreadyConfirmed,
                'remaining_quantity'  => $remaining,
                'confirmed_quantity'  => $remaining, // pre-fill with what's left
                'notes'               => $item->notes ?? '',
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
            // Validate against remaining, not the full requested quantity
            $max = $data['remaining_quantity'] ?? $data['requested_quantity'];
            $rules["receivedItems.{$itemId}.confirmed_quantity"] = "required|numeric|min:0|max:{$max}";
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
            $itemName = __('inventory::modules.transfers.item_fallback');
            
            if ($item && $item->destinationItem) {
                $itemName = $item->destinationItem->name;
            }
            
            $maxQty = $data['remaining_quantity'] ?? ($data['requested_quantity'] ?? 0);
            
            $messages["receivedItems.{$itemId}.confirmed_quantity.required"] = __('inventory::modules.transfers.confirmed_quantity_required', ['item' => $itemName]);
            $messages["receivedItems.{$itemId}.confirmed_quantity.numeric"] = __('inventory::modules.transfers.confirmed_quantity_numeric', ['item' => $itemName]);
            $messages["receivedItems.{$itemId}.confirmed_quantity.min"] = __('inventory::modules.transfers.confirmed_quantity_min', ['item' => $itemName]);
            $messages["receivedItems.{$itemId}.confirmed_quantity.max"] = __('inventory::modules.transfers.confirmed_quantity_max', ['item' => $itemName, 'max' => $maxQty]);
        }
        
        return $messages;
    }

    public function confirmReceive()
    {
        abort_if(!user_can('Update Stock Transfer'), 403);

        if (!$this->transfer) return;

        $this->validate();

        try {
            DB::transaction(function () {

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

                    $alreadyConfirmed   = (float)($item->confirmed_quantity ?? 0);
                    $newTotalConfirmed  = $alreadyConfirmed + (float)$confirmedQty;

                    // Update transfer item — accumulate confirmed quantity
                    $item->confirmed_quantity = $newTotalConfirmed;
                    $item->notes = $receivedData['notes'] ?? null;

                    if ($newTotalConfirmed >= $item->requested_quantity) {
                        $item->status = 'completed';
                    } else {
                        $item->status = 'partially_received';
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

                    // Create a movement record for this receive session (one row per partial receive for audit trail)
                    $sourceItem = InventoryItem::find($item->source_inventory_item_id);
                    InventoryMovement::create([
                        'branch_id'                  => branch()->id,
                        'location_id'                => $this->transfer->destination_location_id,
                        'inventory_item_id'          => $item->destination_inventory_item_id,
                        'transaction_type'           => 'in',
                        'quantity'                   => $confirmedQty,
                        'unit_purchase_price'        => $sourceItem->unit_purchase_price ?? null,
                        'added_by'                   => user()->id,
                        'transfer_branch_id'         => $this->transfer->source_branch_id,
                        'inventory_transfer_id'      => $this->transfer->id,
                        'inventory_transfer_item_id' => $item->id,
                    ]);

                    // Update menu item status
                    if ($item->destinationItem) {
                        $item->destinationItem->menuItems()->update([
                            'in_stock' => 1
                        ]);
                    }
                }

                // Update transfer status based on item statuses
                $itemStatuses = $this->transfer->items()->pluck('status')->toArray();
                $hasCompleted         = in_array('completed', $itemStatuses);
                $hasPartiallyReceived = in_array('partially_received', $itemStatuses);
                $hasPending           = in_array('pending', $itemStatuses);
                $hasInTransit         = in_array('in_transit', $itemStatuses);

                if (!$hasPending && !$hasPartiallyReceived && !$hasInTransit) {
                    // Every item is completed
                    $this->transfer->status = 'completed';
                } elseif ($hasCompleted || $hasPartiallyReceived) {
                    // Some received — remain in transit for further receives
                    $this->transfer->status = 'in_transit';
                } else {
                    $this->transfer->status = 'pending';
                }

                $this->transfer->confirmed_by = user()->id;
                $this->transfer->confirmed_at = now();
                $this->transfer->save();
            });

            // Reload from DB so re-render reflects updated quantities before modal closes
            $this->transfer = InventoryTransfer::with([
                'items.sourceItem.unit',
                'items.destinationItem.unit',
                'sourceLocation',
                'destinationLocation',
            ])->find($this->transfer->id);
            $this->loadReceivedItems();

            $this->alert('success', __('inventory::modules.transfers.transfer_confirmed_successfully'));
            $this->dispatch('transferReceived');
            $this->dispatch('closeReceiveModal');
            
        } catch (\Exception $e) {
            Log::error('Stock transfer receive failed: ' . $e->getMessage());
            $this->alert('error', __('inventory::modules.transfers.transfer_confirmation_failed'));
        }
    }

    public function render()
    {
        return view('inventory::livewire.stock-transfer.receive-stock-transfer');
    }
}

