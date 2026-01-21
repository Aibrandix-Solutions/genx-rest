<?php

namespace Modules\Inventory\Livewire\Stock;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\PurchaseLocation;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\InventoryStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddStockEntry extends Component
{
    use LivewireAlert;
    public $transactionType;
    public $inventoryItem;
    public $quantity;
    public $supplier = null;
    public $expiryDate;
    public $inventoryItems;
    public $suppliers;
    public $wasteReason;
    public $locations = [];
    public $location_id;
    public $search = '';
    public $showDropdown = false;
    public $selectedItem = null;
    public $unitPurchasePrice = 0;
    public $expirationDate;
    public $destinationInventoryItem;
    public $destinationInventoryItems = [];

    protected $listeners = [
        'item-selected' => 'onItemSelected',
        'supplier-selected' => 'onSupplierSelected'
    ];

    public function mount()
    {
        $this->inventoryItems = InventoryItem::with('category')->get();
        $this->suppliers = Supplier::all();
        $this->locations = PurchaseLocation::getForRestaurant(restaurant()->id);
        $this->transactionType = 'in';
    }

    public function rules()
    {
        return [
            'inventoryItem' => 'required',
            'quantity' => 'required|numeric',
            'supplier' => 'nullable|exists:suppliers,id',
            'wasteReason' => 'required_if:transactionType,waste',
            'location_id' => 'required|exists:purchase_locations,id',
            'unitPurchasePrice' => 'required_if:transactionType,in|nullable|numeric',
            'expirationDate' => 'nullable|required_if:transactionType,in|date',
        ];
    }

    public function submitForm()
    {
        // DEBUG: Log form submission start
        Log::info('[STOCK ENTRY] Form submission started', [
            'transactionType' => $this->transactionType,
            'inventoryItem' => $this->inventoryItem,
            'quantity' => $this->quantity,
            'location_id' => $this->location_id,
            'current_branch_id' => branch()->id,
            'all_properties' => [
                'transactionType' => $this->transactionType,
                'inventoryItem' => $this->inventoryItem,
                'location_id' => $this->location_id,
                'quantity' => $this->quantity,
            ]
        ]);

        try {
            $this->validate();
            Log::info('[TRANSFER DEBUG] Validation passed');

            DB::transaction(function () {
                if ($this->transactionType === 'transfer') {
                    $this->alert('info', __('inventory::modules.transfers.use_transfers_page'));
                    throw new \Exception(__('inventory::modules.transfers.use_transfers_page'));
                }

                // Create movement for location-based operation
                $movement = new InventoryMovement();
                $movement->branch_id = branch()->id;
                $movement->location_id = $this->location_id;
                $movement->inventory_item_id = $this->inventoryItem;
                $movement->quantity = $this->quantity;
                $movement->transaction_type = $this->transactionType;
                $movement->supplier_id = ($this->transactionType == 'in' && !empty($this->supplier)) ? $this->supplier : null;
                $movement->waste_reason = ($this->transactionType == 'waste') ? $this->wasteReason : null;
                $movement->unit_purchase_price = $this->unitPurchasePrice;
                $movement->added_by = user()->id;
                $movement->expiration_date = ($this->transactionType == 'in' && !empty($this->expirationDate)) ? $this->expirationDate : null;
                $movement->save();

                // Get or create stock bucket by location
                $stock = InventoryStock::where('inventory_item_id', $this->inventoryItem)
                    ->where('location_id', $this->location_id)
                    ->firstOrCreate([
                        'inventory_item_id' => $this->inventoryItem,
                        'location_id' => $this->location_id,
                        'branch_id' => branch()->id,
                    ], [
                        'quantity' => 0,
                    ]);

                if ($this->transactionType == 'in') {
                    $stock->quantity += $this->quantity;
                    $stock->save();

                    $inventoryItem = InventoryItem::where('id', $this->inventoryItem)->first();
                    if ($inventoryItem) {
                        $inventoryItem->menuItems()->update(['in_stock' => 1]);
                    }
                } else { // out or waste
                    if ($stock->quantity < $this->quantity) {
                        throw new \Exception(__('inventory::modules.stock.insufficientStock', [
                            'available' => $stock->quantity,
                            'required' => $this->quantity,
                        ]));
                    }
                    $stock->quantity -= $this->quantity;
                    $stock->save();
                }
            });

            Log::info('[STOCK ENTRY] Transaction completed successfully');
            
            $this->alert('success', __('inventory::modules.stock.stockEntryAddedSuccessfully'));
            
            $this->dispatch('hideAddStockEntryModal');
            $this->reset(['inventoryItem', 'quantity', 'supplier', 'wasteReason', 'location_id', 'destinationInventoryItem', 'unitPurchasePrice', 'expirationDate']);
        } catch (\Exception $e) {
            Log::error('[STOCK ENTRY] Exception caught', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->alert('error', $e->getMessage());
        }
    }

    public function updatedSearch()
    {
        $this->showDropdown = strlen($this->search) > 0;
    }

    public function selectItem($itemId)
    {
        $this->inventoryItem = $itemId;
        $this->selectedItem = InventoryItem::find($itemId);
        $this->search = $this->selectedItem->name;
        $this->showDropdown = false;
    }

    public function clearSelection()
    {
        $this->inventoryItem = null;
        $this->selectedItem = null;
        $this->search = '';
        $this->showDropdown = false;
    }

    public function onItemSelected($itemId)
    {
        $this->inventoryItem = $itemId;
        $inventoryItem = InventoryItem::find($itemId);
        $this->unitPurchasePrice = $inventoryItem->unit_purchase_price;
        $this->supplier = $inventoryItem->preferred_supplier_id;
    }

    public function onSupplierSelected($supplierId)
    {
        $this->supplier = $supplierId;
    }

    // Transfer is now handled on dedicated page; no branch updater needed.

    public function updatedTransactionType($value)
    {
        Log::info('[STOCK ENTRY] Transaction type changed', [
            'value' => $value,
        ]);
        
        $this->destinationInventoryItems = [];
        $this->destinationInventoryItem = null;
        // Clear expiration date for non-'in' transactions
        if ($value !== 'in') {
            $this->expirationDate = null;
        }
    }

    public function render()
    {
        $searchResults = [];
        if (strlen($this->search) > 0) {
            $searchResults = InventoryItem::where('name', 'like', '%' . $this->search . '%')
                ->orWhereHas('category', function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->take(5)
                ->get();
        }

        return view('inventory::livewire.stock.add-stock-entry', [
            'searchResults' => $searchResults,
            'locations' => $this->locations,
        ]);
    }
}
