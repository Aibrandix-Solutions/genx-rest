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
            
        // Load source items from current branch (BranchScope will filter automatically)
        $this->availableItems = InventoryItem::with(['category', 'unit'])
            ->orderBy('name')
            ->get();
        $this->resetForm();
    }

    public function updatedDestinationLocation()
    {
        if ($this->destinationLocation) {
            // Get the branch_id from the destination location
            $destLocation = PurchaseLocation::find($this->destinationLocation);
            
            if ($destLocation && $destLocation->branch_id) {
                // Load items from the destination branch
                $this->destinationItems = InventoryItem::withoutGlobalScopes()
                    ->where('branch_id', $destLocation->branch_id)
                    ->with(['category', 'unit'])
                    ->orderBy('name')
                    ->get();
            } else {
                $this->destinationItems = [];
            }
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
                    $sourceItem = InventoryItem::withoutGlobalScopes()->find($itemId);
                    
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
                }
            }
        }
    }

    public function rules()
    {
        return [
            'sourceLocation' => 'required|exists:purchase_locations,id',
            'destinationLocation' => 'required|exists:purchase_locations,id|different:sourceLocation',
            'expectedDeliveryDate' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'transferItems' => 'required|array|min:1',
            'transferItems.*.source_item_id' => 'required|exists:inventory_items,id',
            'transferItems.*.destination_item_id' => 'nullable', // Allow null - will auto-create if needed
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
        $this->validate();

        // Get source and destination location details
        $sourceLocation = PurchaseLocation::find($this->sourceLocation);
        $destinationLocation = PurchaseLocation::find($this->destinationLocation);

        // Validate stock availability at the source location
        foreach ($this->transferItems as $index => $item) {
            $stock = InventoryStock::where('inventory_item_id', $item['source_item_id'])
                ->where('location_id', $this->sourceLocation)
                ->first();
            
            $currentStock = $stock ? (float)$stock->quantity : 0;
            
            // Calculate pending transfers from this location
            $pendingTransfersQuantity = (float)InventoryTransferItem::whereHas('transfer', function($query) {
                    $query->where('source_location_id', $this->sourceLocation)
                          ->where('status', 'pending');
                })
                ->where('source_inventory_item_id', $item['source_item_id'])
                ->sum('requested_quantity');
            
            $available = max(0, $currentStock - $pendingTransfersQuantity);
            
            if ($available < $item['quantity']) {
                $this->addError("transferItems.{$index}.quantity", 
                    __('inventory::modules.transfers.insufficient_stock', [
                        'available' => $available,
                        'requested' => $item['quantity']
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

                // Create transfer items and auto-create destination items if needed
                foreach ($this->transferItems as $item) {
                    $destinationItemId = $item['destination_item_id'];
                    
                    // Check if destination item exists, if not create it
                    $destBranchId = $destinationLocation->branch_id ?? branch()->id;
                    $destItemExists = InventoryItem::withoutGlobalScopes()
                        ->where('id', $destinationItemId)
                        ->where('branch_id', $destBranchId)
                        ->exists();
                    
                    if (!$destItemExists) {
                        // Get source item details
                        $sourceItem = InventoryItem::withoutGlobalScopes()->find($item['source_item_id']);
                        
                        if ($sourceItem) {
                            // Create destination item as a copy of source item
                            $newDestItem = InventoryItem::create([
                                'name' => $sourceItem->name,
                                'branch_id' => $destBranchId,
                                'restaurant_id' => restaurant()->id,
                                'inventory_item_category_id' => $sourceItem->inventory_item_category_id,
                                'unit_id' => $sourceItem->unit_id,
                                'unit_purchase_price' => $sourceItem->unit_purchase_price,
                                'unit_selling_price' => $sourceItem->unit_selling_price,
                                'threshold_quantity' => $sourceItem->threshold_quantity,
                                'description' => $sourceItem->description,
                                'sku' => $sourceItem->sku,
                            ]);
                            
                            $destinationItemId = $newDestItem->id;
                        }
                    }
                    
                    InventoryTransferItem::create([
                        'inventory_transfer_id' => $transfer->id,
                        'source_inventory_item_id' => $item['source_item_id'],
                        'destination_inventory_item_id' => $destinationItemId,
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

