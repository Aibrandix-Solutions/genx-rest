<?php

namespace Modules\Inventory\Livewire\StockTransfer;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\PurchaseLocation;
use App\Models\Branch;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CreateStockTransfer extends Component
{
    use LivewireAlert;

    public $sourceLocation;
    public $destinationLocation;
    public $expectedDeliveryDate;
    public $notes;
    public $transferItems = [];
    public $availableLocations = [];
    public $availableItems = [];
    public $destinationItems = [];

    protected $listeners = [
        'transferCreated' => '$refresh',
    ];

    public function mount()
    {
        // Load all active locations for the restaurant
        $this->availableLocations = PurchaseLocation::where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Items are NOT loaded until a source location is chosen
        $this->availableItems = collect();
        $this->resetForm();
    }

    public function updatedSourceLocation()
    {
        // Load items that have stock at this location
        if ($this->sourceLocation) {
            $this->availableItems = InventoryItem::with(['category', 'unit'])
                ->where('restaurant_id', restaurant()->id)
                ->whereHas('stocks', function ($q) {
                    $q->where('location_id', $this->sourceLocation)
                      ->where('quantity', '>', 0);
                })
                ->orderBy('name')
                ->get();
        } else {
            $this->availableItems = collect();
        }

        // Reset items already added since source changed
        $this->transferItems = [];
    }

    public function updatedDestinationLocation()
    {
        if ($this->destinationLocation) {
            // Load all items (items are now restaurant-scoped, not branch-scoped)
            $this->destinationItems = InventoryItem::with(['category', 'unit'])
                ->where('restaurant_id', restaurant()->id)
                ->orderBy('name')
                ->get();
        } else {
            $this->destinationItems = [];
        }
        
        // Reset destination item selections when location changes
        foreach ($this->transferItems as $index => $item) {
            $this->transferItems[$index]['destination_item_id'] = null;
        }
    }

    public function addTransferItem()
    {
        $this->transferItems[] = [
            'source_item_id' => null,
            'destination_item_id' => null,
            'quantity' => null,
            'unit_id' => null,
            'available_stock' => 0,
        ];
    }

    public function removeTransferItem($index)
    {
        unset($this->transferItems[$index]);
        $this->transferItems = array_values($this->transferItems);
    }

    public function updatedTransferItems($value, $key)
    {
        // When source item is selected, check available stock and suggest matching destination item
        if (str_contains($key, '.source_item_id')) {
            $parts = explode('.', $key);
            if (count($parts) >= 2 && is_numeric($parts[0])) {
                $index = (int)$parts[0];
                $itemId = $value;
                
                if ($itemId && isset($this->transferItems[$index])) {
                    // Get source item details
                    $sourceItem = InventoryItem::query()
                        ->where('restaurant_id', restaurant()->id)
                        ->find($itemId);
                    
                    // Get available stock from the specific source location if selected
                    if ($this->sourceLocation) {
                        $stock = InventoryStock::where('inventory_item_id', $itemId)
                            ->where('location_id', $this->sourceLocation)
                            ->first();
                    } else {
                        // Default to current branch
                        $stock = InventoryStock::where('inventory_item_id', $itemId)
                            ->where('branch_id', branch()->id)
                            ->first();
                    }
                    
                    $currentStock = $stock ? (float)$stock->quantity : 0;
                    
                    // Calculate pending transfers from this location
                    $pendingTransfersQuantity = (float)InventoryTransferItem::whereHas('transfer', function($query) {
                            $query->where('status', 'pending');
                            if ($this->sourceLocation) {
                                $query->where('source_location_id', $this->sourceLocation);
                            } else {
                                $query->where('source_branch_id', branch()->id);
                            }
                        })
                        ->where('source_inventory_item_id', $itemId)
                        ->sum('requested_quantity');
                    
                    // Available stock = current stock - pending transfers
                    $availableStock = max(0, $currentStock - $pendingTransfersQuantity);
                    
                    $this->transferItems[$index]['available_stock'] = $availableStock;

                    // Auto-set unit from selected item
                    if ($sourceItem && $sourceItem->unit_id) {
                        $this->transferItems[$index]['unit_id'] = $sourceItem->unit_id;
                    }

                    // Auto-suggest matching destination item by name (if destination location is selected)
                    if ($this->destinationLocation && $sourceItem && count($this->destinationItems) > 0) {
                        $matchingItem = $this->destinationItems->first(function($item) use ($sourceItem) {
                            return strtolower(trim($item->name)) === strtolower(trim($sourceItem->name));
                        });
                        
                        if ($matchingItem && empty($this->transferItems[$index]['destination_item_id'])) {
                            $this->transferItems[$index]['destination_item_id'] = $matchingItem->id;
                        }
                    }
                } elseif (isset($this->transferItems[$index])) {
                    $this->transferItems[$index]['available_stock'] = 0;
                    $this->transferItems[$index]['destination_item_id'] = null;
                    $this->transferItems[$index]['unit_id'] = null;
                }
            }
        }
    }

    public function rules()
    {
        $restaurantId = restaurant()->id;

        return [
            'sourceLocation'      => ['required', Rule::exists('purchase_locations', 'id')->where('restaurant_id', $restaurantId)],
            'destinationLocation' => ['required', 'different:sourceLocation', Rule::exists('purchase_locations', 'id')->where('restaurant_id', $restaurantId)],
            'expectedDeliveryDate' => 'nullable|date|after_or_equal:today',
            'notes'        => 'nullable|string|max:1000',
            'transferItems' => 'required|array|min:1',
            'transferItems.*.source_item_id' => ['required', Rule::exists('inventory_items', 'id')->where('restaurant_id', $restaurantId)],
            'transferItems.*.destination_item_id' => 'nullable',
            'transferItems.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    public function messages()
    {
        return [
            'sourceLocation.required' => __('inventory::modules.transfers.source_location_required'),
            'destinationLocation.required' => __('inventory::modules.transfers.destination_location_required'),
            'destinationLocation.different' => __('inventory::modules.transfers.destination_must_differ'),
            'transferItems.required' => __('inventory::modules.transfers.at_least_one_item_required'),
            'transferItems.*.source_item_id.required' => __('inventory::modules.transfers.source_item_required'),
            'transferItems.*.quantity.required' => __('inventory::modules.transfers.quantity_required'),
            'transferItems.*.quantity.min' => __('inventory::modules.transfers.quantity_min'),
        ];
    }

    public function createTransfer()
    {
        abort_if(!user_can('Create Stock Transfer'), 403);

        $this->validate();

        // Get source and destination location details
        $sourceLocation = PurchaseLocation::find($this->sourceLocation);
        $destinationLocation = PurchaseLocation::find($this->destinationLocation);

        // Aggregate total requested per source item to catch duplicate item rows
        $requestedTotals = [];
        foreach ($this->transferItems as $item) {
            $id = $item['source_item_id'];
            $requestedTotals[$id] = ($requestedTotals[$id] ?? 0) + (float)$item['quantity'];
        }

        // Validate stock availability — one DB check per unique item
        $checked = [];
        foreach ($this->transferItems as $index => $item) {
            $itemId = $item['source_item_id'];
            if (isset($checked[$itemId])) {
                continue;
            }
            $checked[$itemId] = true;

            $stock = InventoryStock::where('inventory_item_id', $itemId)
                ->where('location_id', $this->sourceLocation)
                ->first();

            $currentStock = $stock ? (float)$stock->quantity : 0;

            // Calculate pending transfers from this location
            $pendingTransfersQuantity = (float)InventoryTransferItem::whereHas('transfer', function($query) {
                    $query->where('source_location_id', $this->sourceLocation)
                          ->where('status', 'pending');
                })
                ->where('source_inventory_item_id', $itemId)
                ->sum('requested_quantity');

            $available     = max(0, $currentStock - $pendingTransfersQuantity);
            $totalRequested = $requestedTotals[$itemId];

            if ($available < $totalRequested) {
                $this->addError("transferItems.{$index}.quantity",
                    __('inventory::modules.transfers.insufficient_stock', [
                        'available' => $available,
                        'requested' => $totalRequested,
                    ])
                );
                return;
            }
        }

        try {
            DB::transaction(function () use ($sourceLocation, $destinationLocation) {
                // Determine branch IDs - for warehouses, branch_id might be null
                // In that case, we use the current branch as fallback
                $sourceBranchId = $sourceLocation->branch_id ?? branch()->id;
                $destinationBranchId = $destinationLocation->branch_id ?? branch()->id;

                // Create transfer
                $transfer = InventoryTransfer::create([
                    'restaurant_id' => restaurant()->id,
                    'transfer_number' => InventoryTransfer::generateTransferNumber(),
                    'source_branch_id' => $sourceBranchId,
                    'destination_branch_id' => $destinationBranchId,
                    'source_location_id' => $this->sourceLocation,
                    'destination_location_id' => $this->destinationLocation,
                    'status' => 'pending',
                    'notes' => $this->notes,
                    'expected_delivery_date' => $this->expectedDeliveryDate,
                    'created_by' => user()->id,
                ]);

                // Create transfer items - now simple since items are shared!
                foreach ($this->transferItems as $item) {
                    // With restaurant-scoped items, source and destination use the SAME item
                    InventoryTransferItem::create([
                        'inventory_transfer_id' => $transfer->id,
                        'source_inventory_item_id' => $item['source_item_id'],
                        'destination_inventory_item_id' => $item['source_item_id'], // Same item!
                        'unit_id' => $item['unit_id'] ?: null,
                        'requested_quantity' => $item['quantity'],
                        'status' => 'pending',
                    ]);
                }
            });

            $this->alert('success', __('inventory::modules.transfers.transfer_created_successfully'));
            $this->resetForm();
            $this->dispatch('transferCreated');
            $this->dispatch('closeCreateTransferModal');
            
        } catch (\Exception $e) {
            Log::error('Error creating transfer: ' . $e->getMessage());
            $this->alert('error', __('inventory::modules.transfers.transfer_creation_failed'));
        }
    }

    public function openModal()
    {
        $this->resetForm();
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->dispatch('closeCreateTransferModal');
    }

    public function resetForm()
    {
        $this->sourceLocation = null;
        $this->destinationLocation = null;
        $this->expectedDeliveryDate = null;
        $this->notes = null;
        $this->transferItems = [];
        $this->destinationItems = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('inventory::livewire.stock-transfer.create-stock-transfer');
    }
}

