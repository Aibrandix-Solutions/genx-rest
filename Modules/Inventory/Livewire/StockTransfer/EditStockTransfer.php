<?php

namespace Modules\Inventory\Livewire\StockTransfer;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EditStockTransfer extends Component
{
    use LivewireAlert;

    public $transferId;
    public $transfer;

    // Header fields (source/destination are read-only after creation)
    public $sourceLocation;
    public $destinationLocation;
    public $expectedDeliveryDate;
    public $notes;

    public $transferItems = [];
    public $availableItems; // collection

    protected $listeners = [
        'transferUpdated' => '$refresh',
    ];

    public function mount(InventoryTransfer $transfer)
    {
        if ($transfer->status !== 'pending') {
            $this->alert('error', __('inventory::modules.transfers.only_pending_can_be_edited'));
            $this->dispatch('closeEditTransferModal');
            return;
        }

        $this->transfer   = $transfer->load(['items.sourceItem.unit', 'sourceLocation', 'destinationLocation']);
        $this->transferId = $transfer->id;

        $this->sourceLocation      = $transfer->source_location_id;
        $this->destinationLocation = $transfer->destination_location_id;
        $this->expectedDeliveryDate = $transfer->expected_delivery_date?->format('Y-m-d');
        $this->notes               = $transfer->notes;

        // All items in the restaurant — no stock filter so existing-item names always appear
        $this->availableItems = InventoryItem::with(['unit'])
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();

        // Pre-fill rows from existing transfer items
        foreach ($transfer->items as $existingItem) {
            $stock = InventoryStock::where('inventory_item_id', $existingItem->source_inventory_item_id)
                ->where('location_id', $this->sourceLocation)
                ->first();
            $physicalStock = $stock ? (float) $stock->quantity : 0;

            // Available = physical stock minus reservations from OTHER pending transfers at this location
            // Do NOT add back own reservation — pending transfers never deducted physical stock
            $otherPendingQty = (float) InventoryTransferItem::whereHas('transfer', function ($q) {
                $q->where('source_location_id', $this->sourceLocation)
                  ->where('status', 'pending')
                  ->where('id', '!=', $this->transferId);
            })->where('source_inventory_item_id', $existingItem->source_inventory_item_id)
              ->sum('requested_quantity');

            $itemAvailable = max(0, $physicalStock - $otherPendingQty);

            $this->transferItems[] = [
                'transfer_item_id' => $existingItem->id,
                'source_item_id'   => $existingItem->source_inventory_item_id,
                'quantity'         => $existingItem->requested_quantity,
                'unit_id'          => $existingItem->unit_id,
                'available_stock'  => $itemAvailable,
            ];
        }
    }

    public function addTransferItem()
    {
        $this->transferItems[] = [
            'transfer_item_id' => null,
            'source_item_id'   => null,
            'quantity'         => null,
            'unit_id'          => null,
            'available_stock'  => 0,
        ];
    }

    public function removeTransferItem($index)
    {
        unset($this->transferItems[$index]);
        $this->transferItems = array_values($this->transferItems);
    }

    public function updatedTransferItems($value, $key)
    {
        if (str_contains($key, '.source_item_id')) {
            $parts = explode('.', $key);
            if (count($parts) >= 2 && is_numeric($parts[0])) {
                $index  = (int) $parts[0];
                $itemId = $value;

                if ($itemId && isset($this->transferItems[$index])) {
                    $sourceItem = $this->availableItems->find($itemId);

                    $stock = InventoryStock::where('inventory_item_id', $itemId)
                        ->where('location_id', $this->sourceLocation)
                        ->first();
                    $currentStock = $stock ? (float) $stock->quantity : 0;

                    // Subtract reservations from other pending transfers (excluding this one)
                    $otherPendingQty = (float) InventoryTransferItem::whereHas('transfer', function ($q) {
                        $q->where('source_location_id', $this->sourceLocation)
                          ->where('status', 'pending')
                          ->where('id', '!=', $this->transferId);
                    })->where('source_inventory_item_id', $itemId)->sum('requested_quantity');

                    $this->transferItems[$index]['available_stock'] = max(0, $currentStock - $otherPendingQty);

                    if ($sourceItem?->unit_id) {
                        $this->transferItems[$index]['unit_id'] = $sourceItem->unit_id;
                    }
                } elseif (isset($this->transferItems[$index])) {
                    $this->transferItems[$index]['available_stock'] = 0;
                    $this->transferItems[$index]['unit_id']         = null;
                }
            }
        }
    }

    public function rules()
    {
        $restaurantId = restaurant()->id;

        return [
            'notes'                          => 'nullable|string|max:1000',
            'expectedDeliveryDate'           => 'nullable|date|after_or_equal:today',
            'transferItems'                  => 'required|array|min:1',
            'transferItems.*.source_item_id' => ['required', Rule::exists('inventory_items', 'id')->where('restaurant_id', $restaurantId)],
            'transferItems.*.quantity'       => 'required|numeric|min:0.01',
        ];
    }

    public function updateTransfer()
    {
        abort_if(!user_can('Update Stock Transfer'), 403);

        $this->validate();

        // Aggregate new totals per item
        $requestedTotals = [];
        foreach ($this->transferItems as $item) {
            $id = $item['source_item_id'];
            $requestedTotals[$id] = ($requestedTotals[$id] ?? 0) + (float) $item['quantity'];
        }

        // What was originally reserved in this transfer per item (to add back when computing availability)
        $existingReserved = [];
        foreach ($this->transfer->items as $existingItem) {
            $id = $existingItem->source_inventory_item_id;
            $existingReserved[$id] = ($existingReserved[$id] ?? 0) + (float) $existingItem->requested_quantity;
        }

        // Validate stock availability per unique item
        $checked = [];
        foreach ($this->transferItems as $index => $item) {
            $itemId = $item['source_item_id'];
            if (isset($checked[$itemId])) {
                continue;
            }
            $checked[$itemId] = true;

            $stock        = InventoryStock::where('inventory_item_id', $itemId)
                ->where('location_id', $this->sourceLocation)
                ->first();
            $currentStock = $stock ? (float) $stock->quantity : 0;

            // Add back what this transfer currently has reserved
            $available = $currentStock + ($existingReserved[$itemId] ?? 0);

            // Subtract pending reservations from OTHER transfers at this location
            $otherPending = (float) InventoryTransferItem::whereHas('transfer', function ($q) {
                $q->where('source_location_id', $this->sourceLocation)
                  ->where('status', 'pending')
                  ->where('id', '!=', $this->transferId);
            })->where('source_inventory_item_id', $itemId)->sum('requested_quantity');

            $trulyAvailable = max(0, $available - $otherPending);

            if ($trulyAvailable < $requestedTotals[$itemId]) {
                $this->addError("transferItems.{$index}.quantity",
                    __('inventory::modules.transfers.insufficient_stock', [
                        'available' => $trulyAvailable,
                        'requested' => $requestedTotals[$itemId],
                    ])
                );
                return;
            }
        }

        try {
            DB::transaction(function () {
                // Update header
                $this->transfer->notes                 = $this->notes;
                $this->transfer->expected_delivery_date = $this->expectedDeliveryDate;
                $this->transfer->save();

                // Determine which existing item IDs are still present in the form
                $keptItemIds = collect($this->transferItems)
                    ->pluck('transfer_item_id')
                    ->filter()
                    ->values()
                    ->all();

                // Delete rows that were removed
                $this->transfer->items()
                    ->whereNotIn('id', $keptItemIds)
                    ->delete();

                // Update or create each row
                foreach ($this->transferItems as $row) {
                    if ($row['transfer_item_id']) {
                        InventoryTransferItem::where('id', $row['transfer_item_id'])->update([
                            'source_inventory_item_id'      => $row['source_item_id'],
                            'destination_inventory_item_id' => $row['source_item_id'],
                            'requested_quantity'            => $row['quantity'],
                            'unit_id'                       => $row['unit_id'] ?: null,
                        ]);
                    } else {
                        InventoryTransferItem::create([
                            'inventory_transfer_id'         => $this->transferId,
                            'source_inventory_item_id'      => $row['source_item_id'],
                            'destination_inventory_item_id' => $row['source_item_id'],
                            'unit_id'                       => $row['unit_id'] ?: null,
                            'requested_quantity'            => $row['quantity'],
                            'status'                        => 'pending',
                        ]);
                    }
                }
            });

            $this->alert('success', __('inventory::modules.transfers.transfer_updated_successfully'));
            $this->dispatch('transferUpdated');
            $this->dispatch('closeEditTransferModal');

        } catch (\Exception $e) {
            Log::error('Edit transfer failed: ' . $e->getMessage());
            $this->alert('error', __('inventory::modules.transfers.transfer_update_failed'));
        }
    }

    public function render()
    {
        return view('inventory::livewire.stock-transfer.edit-stock-transfer');
    }
}
