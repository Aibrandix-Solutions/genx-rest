<?php

namespace Modules\Inventory\Livewire\Stock;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\Supplier;
use App\Models\Branch;
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
    public $branches;
    public $branch;
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
        $this->branches = Branch::where('id', '!=', branch()->id)->get();
        $this->transactionType = 'in';
    }

    public function rules()
    {
        return [
            'inventoryItem' => 'required',
            'quantity' => 'required|numeric',
            'supplier' => 'required_if:transactionType,in',
            'wasteReason' => 'required_if:transactionType,waste',
            'branch' => 'required_if:transactionType,transfer',
            'destinationInventoryItem' => 'required_if:transactionType,transfer',
            'unitPurchasePrice' => 'required_if:transactionType,in|nullable|numeric',
            'expirationDate' => 'nullable|required_if:transactionType,in|date',
        ];
    }

    public function submitForm()
    {
        // DEBUG: Log form submission start
        Log::info('[TRANSFER DEBUG] Form submission started', [
            'transactionType' => $this->transactionType,
            'inventoryItem' => $this->inventoryItem,
            'quantity' => $this->quantity,
            'branch' => $this->branch,
            'destinationInventoryItem' => $this->destinationInventoryItem,
            'current_branch_id' => branch()->id,
            'all_properties' => [
                'transactionType' => $this->transactionType,
                'inventoryItem' => $this->inventoryItem,
                'branch' => $this->branch,
                'destinationInventoryItem' => $this->destinationInventoryItem,
                'quantity' => $this->quantity,
            ]
        ]);

        try {
            $this->validate();
            Log::info('[TRANSFER DEBUG] Validation passed');

            DB::transaction(function () {
                // For transfer, validate inputs and source stock availability first
                if ($this->transactionType == 'transfer') {
                    Log::info('[TRANSFER DEBUG] Transfer transaction detected');
                    
                    // Ensure branch is integer
                    $destinationBranchId = is_numeric($this->branch) ? (int)$this->branch : null;
                    $destinationItemId = is_numeric($this->destinationInventoryItem) ? (int)$this->destinationInventoryItem : null;
                    
                    if (empty($destinationBranchId)) {
                        Log::error('[TRANSFER DEBUG] Branch is empty', ['branch_value' => $this->branch]);
                        throw new \Exception('Please select a destination branch.');
                    }
                    if (empty($destinationItemId)) {
                        Log::error('[TRANSFER DEBUG] Destination inventory item is empty', ['item_value' => $this->destinationInventoryItem]);
                        throw new \Exception('Please select a destination inventory item.');
                    }
                    
                    Log::info('[TRANSFER DEBUG] Checking source stock', [
                        'source_inventory_item_id' => $this->inventoryItem,
                        'source_branch_id' => branch()->id,
                        'transfer_quantity' => $this->quantity
                    ]);
                    
                    $sourceStock = InventoryStock::where('inventory_item_id', $this->inventoryItem)
                        ->where('branch_id', branch()->id)
                        ->first();

                    Log::info('[TRANSFER DEBUG] Source stock query result', [
                        'sourceStock_exists' => $sourceStock !== null,
                        'sourceStock_id' => $sourceStock?->id,
                        'sourceStock_quantity' => $sourceStock?->quantity,
                        'required_quantity' => $this->quantity,
                        'sufficient' => $sourceStock && $sourceStock->quantity >= $this->quantity
                    ]);

                    if (!$sourceStock || $sourceStock->quantity < $this->quantity) {
                        $available = $sourceStock ? $sourceStock->quantity : 0;
                        Log::error('[TRANSFER DEBUG] Insufficient stock', [
                            'available' => $available,
                            'required' => $this->quantity
                        ]);
                        throw new \Exception(__('inventory::modules.stock.insufficientStock', [
                            'available' => $available,
                            'required' => $this->quantity
                        ]));
                    }
                }

                // Create source movement (for transfers, this is just a record - stock not deducted yet)
                Log::info('[TRANSFER DEBUG] Creating source movement', [
                    'branch_id' => branch()->id,
                    'inventory_item_id' => $this->inventoryItem,
                    'quantity' => $this->quantity,
                    'transaction_type' => $this->transactionType,
                    'transfer_branch_id' => ($this->transactionType == 'transfer') ? $this->branch : null,
                ]);
                
                $stockEntry = new InventoryMovement();
                $stockEntry->branch_id = branch()->id;
                $stockEntry->inventory_item_id = $this->inventoryItem;
                $stockEntry->quantity = $this->quantity;
                $stockEntry->transaction_type = $this->transactionType;
                $stockEntry->supplier_id = ($this->transactionType == 'in') ? $this->supplier : null;
                $stockEntry->waste_reason = ($this->transactionType == 'waste') ? $this->wasteReason : null;
                $stockEntry->transfer_branch_id = ($this->transactionType == 'transfer' && !empty($this->branch)) ? (int)$this->branch : null;
                $stockEntry->unit_purchase_price = $this->unitPurchasePrice;
                $stockEntry->added_by = user()->id;
                $stockEntry->expiration_date = ($this->transactionType == 'in' && !empty($this->expirationDate)) ? $this->expirationDate : null;
                
                // For transfers, don't save yet - will be linked to transfer and saved later
                if ($this->transactionType != 'transfer') {
                    $stockEntry->save();
                }
                
                Log::info('[TRANSFER DEBUG] Source movement object created', [
                    'transaction_type' => $stockEntry->transaction_type,
                    'is_transfer' => $this->transactionType == 'transfer',
                ]);

                // Get or create source stock BEFORE handling transaction
                Log::info('[TRANSFER DEBUG] Getting/creating source stock');
                $updatedStock = InventoryStock::where('inventory_item_id', $this->inventoryItem)
                    ->where('branch_id', branch()->id)
                    ->firstOrCreate([
                        'inventory_item_id' => $this->inventoryItem,
                        'branch_id' => branch()->id
                    ], [
                        'quantity' => 0
                    ]);
                
                Log::info('[TRANSFER DEBUG] Source stock retrieved', [
                    'stock_id' => $updatedStock->id,
                    'current_quantity' => $updatedStock->quantity,
                    'was_created' => $updatedStock->wasRecentlyCreated
                ]);

                // Handle different transaction types
                if ($this->transactionType == 'in') {
                    $updatedStock->quantity += $this->quantity;
                    $updatedStock->save();

                    $inventoryItem = InventoryItem::where('id', $this->inventoryItem)->first();
                    if ($inventoryItem) {
                        $inventoryItem->menuItems()->update([
                            'in_stock' => 1
                        ]);
                    }
                } elseif ($this->transactionType == 'transfer') {
                    Log::info('[TRANSFER DEBUG] Creating pending transfer record');
                    
                    // Get source item to set unit purchase price
                    $sourceItem = InventoryItem::withoutGlobalScopes()->find($this->inventoryItem);
                    $sourceUnitPrice = $sourceItem ? ($sourceItem->unit_purchase_price ?? 0) : 0;
                    
                    // Create pending transfer record (same workflow as bulk transfers)
                    $transfer = InventoryTransfer::create([
                        'restaurant_id' => restaurant()->id,
                        'transfer_number' => InventoryTransfer::generateTransferNumber(),
                        'source_branch_id' => branch()->id,
                        'destination_branch_id' => $destinationBranchId,
                        'status' => 'pending', // Pending status - must be initiated
                        'created_by' => user()->id,
                    ]);
                    
                    // Create transfer item
                    $transferItem = InventoryTransferItem::create([
                        'inventory_transfer_id' => $transfer->id,
                        'source_inventory_item_id' => $this->inventoryItem,
                        'destination_inventory_item_id' => $destinationItemId,
                        'requested_quantity' => $this->quantity,
                        'status' => 'pending',
                    ]);
                    
                    // Link source movement to transfer and save
                    // Set unit purchase price from source item
                    $stockEntry->unit_purchase_price = $sourceUnitPrice;
                    $stockEntry->inventory_transfer_id = $transfer->id;
                    $stockEntry->inventory_transfer_item_id = $transferItem->id;
                    $stockEntry->save();
                    
                    Log::info('[TRANSFER DEBUG] Transfer created and linked', [
                        'transfer_id' => $transfer->id,
                        'transfer_item_id' => $transferItem->id,
                        'movement_id' => $stockEntry->id,
                    ]);
                    
                    // Note: Stock is NOT deducted at this point
                    // Stock will be deducted when transfer is initiated from Stock Transfers page
                } else {
                    // For 'out' and 'waste'
                    // Validate stock availability first
                    if ($updatedStock->quantity < $this->quantity) {
                        throw new \Exception(__('inventory::modules.stock.insufficientStock', [
                            'available' => $updatedStock->quantity,
                            'required' => $this->quantity
                        ]));
                    }
                    $updatedStock->quantity -= $this->quantity;
                    $updatedStock->save();
                }
            });

            Log::info('[TRANSFER DEBUG] Transaction completed successfully');
            
            if ($this->transactionType == 'transfer') {
                $this->alert('success', __('inventory::modules.transfers.transfer_created_successfully') . '. ' . __('inventory::modules.transfers.initiate_transfer_message'));
                $this->dispatch('transferCreated'); // Notify transfer list to refresh
            } else {
                $this->alert('success', __('inventory::modules.stock.stockEntryAddedSuccessfully'));
            }
            
            $this->dispatch('hideAddStockEntryModal');
            $this->reset(['inventoryItem', 'quantity', 'supplier', 'wasteReason', 'branch', 'destinationInventoryItem', 'unitPurchasePrice', 'expirationDate']);
        } catch (\Exception $e) {
            Log::error('[TRANSFER DEBUG] Exception caught', [
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

    public function updatedBranch($value)
    {
        // Convert string to integer if needed
        $branchId = is_numeric($value) ? (int)$value : $value;
        
        Log::info('[TRANSFER DEBUG] updatedBranch called', [
            'value' => $value,
            'branchId' => $branchId,
            'transactionType' => $this->transactionType,
            'is_transfer' => $this->transactionType === 'transfer'
        ]);
        
        if ($this->transactionType === 'transfer' && $branchId) {
            // Load items only from the selected destination branch (same as stock transfers page)
            // This prevents confusion when items have the same name across branches
            Log::info('[TRANSFER DEBUG] Loading destination items from selected branch', [
                'destination_branch_id' => $branchId
            ]);
            
            $this->destinationInventoryItems = InventoryItem::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->with(['category', 'unit'])
                ->orderBy('name')
                ->get();
            
            Log::info('[TRANSFER DEBUG] Destination items loaded', [
                'count' => $this->destinationInventoryItems->count(),
                'branch_id' => $branchId,
                'items' => $this->destinationInventoryItems->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'branch_id' => $item->branch_id,
                    'category' => $item->category?->name,
                    'unit' => $item->unit?->symbol
                ])->toArray()
            ]);
            
            // Clear destination item selection when branch changes
            $this->destinationInventoryItem = null;
        } else {
            $this->destinationInventoryItems = [];
            $this->destinationInventoryItem = null;
            Log::info('[TRANSFER DEBUG] Cleared destination items');
        }
    }

    public function updatedTransactionType($value)
    {
        Log::info('[TRANSFER DEBUG] updatedTransactionType called', [
            'value' => $value,
            'current_branch' => $this->branch,
            'destinationInventoryItem' => $this->destinationInventoryItem
        ]);
        
        if ($value === 'transfer') {
            // If branch is already set, load destination items
            if ($this->branch) {
                Log::info('[TRANSFER DEBUG] Branch already set, loading destination items');
                $this->updatedBranch($this->branch);
            } else {
                Log::info('[TRANSFER DEBUG] Branch not set yet, waiting for updatedBranch');
            }
        } else {
            $this->destinationInventoryItems = [];
            $this->destinationInventoryItem = null;
            $this->branch = null;
            // Clear expiration date for non-'in' transactions
            if ($value !== 'in') {
                $this->expirationDate = null;
            }
            Log::info('[TRANSFER DEBUG] Cleared transfer-related properties');
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
            'searchResults' => $searchResults
        ]);
    }
}
