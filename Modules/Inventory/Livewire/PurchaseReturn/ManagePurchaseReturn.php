<?php

namespace Modules\Inventory\Livewire\PurchaseReturn;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseReturn;
use Modules\Inventory\Entities\PurchaseReturnItem;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ManagePurchaseReturn extends Component
{
    use LivewireAlert;

    public $showModal = false;
    public $isEditing = false;
    public $purchaseReturn;
    
    // Form fields
    public $purchaseOrderId;
    public $supplierId;
    public $returnDate;
    public $note;
    public $status = 'pending';
    public $items = [];
    public $processImmediately = false;
    protected $listeners = [
        'showPurchaseReturnModal' => 'showModal',
        'editPurchaseReturn' => 'edit',
    ];

    protected function rules()
    {
        return [
            'supplierId' => 'required|exists:suppliers,id',
            'returnDate' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.inventoryItemId' => 'required_with:items.*|exists:inventory_items,id',
            'items.*.quantity' => [
                'required_with:items.*.inventoryItemId',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // Extract index from attribute (e.g., "items.0.quantity" -> 0)
                    preg_match('/items\.(\d+)\.quantity/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    
                    if ($index !== null && isset($this->items[$index]['maxQuantity'])) {
                        $max = (float)$this->items[$index]['maxQuantity'];
                        if ($value > $max) {
                            $fail("Quantity cannot exceed maximum returnable amount of " . number_format($max, 2));
                        }
                    }
                },
            ],
            'items.*.unitPrice' => 'required_with:items.*.inventoryItemId|numeric|min:0',
        ];
    }

    public function mount()
    {
        $this->returnDate = now()->format('Y-m-d');
    }

    public function showModal()
    {
        $this->isEditing = false;
        $this->purchaseReturn = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(...$args)
    {
        // Extract ID from event data - can be passed as object or directly
        $id = null;
        if (!empty($args)) {
            $firstArg = $args[0];
            if (is_array($firstArg)) {
                $id = $firstArg['purchaseReturn'] ?? $firstArg['id'] ?? null;
            } elseif (is_numeric($firstArg)) {
                $id = $firstArg;
            } elseif (is_object($firstArg)) {
                $id = $firstArg->purchaseReturn ?? $firstArg->id ?? null;
            }
        }
        
        if (!$id) {
            return;
        }

        $this->isEditing = true;
        $this->purchaseReturn = PurchaseReturn::find($id);
        
        if (!$this->purchaseReturn) {
            $this->alert('error', 'Purchase return not found.');
            return;
        }
        
        if ($this->purchaseReturn->status === 'completed') {
            $this->alert('error', 'Cannot edit a completed purchase return.');
            return;
        }

        $this->purchaseOrderId = $this->purchaseReturn->purchase_order_id;
        $this->supplierId = $this->purchaseReturn->supplier_id;
        $this->returnDate = $this->purchaseReturn->return_date->format('Y-m-d');
        $this->note = $this->purchaseReturn->note;
        // Status should not be editable - it can only be changed via processReturn()
        $this->status = 'pending'; // Always set to pending (existing status is checked above)

        $this->items = $this->purchaseReturn->items->map(function ($item) {
            return [
                'id' => $item->id,
                'inventoryItemId' => $item->inventory_item_id,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ];
        })->toArray();

        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->reset(['purchaseOrderId', 'supplierId', 'note', 'items']);
        $this->returnDate = now()->format('Y-m-d');
        $this->status = 'pending';
        $this->items = [];
        $this->resetValidation();
    }

   public function updatedPurchaseOrderId($value)
{
    if ($value) {
        $po = PurchaseOrder::find($value);
        if ($po) {
            $this->supplierId = $po->supplier_id;
            
            // Get all previous returns for this purchase (excluding current edit)
            $previousReturns = PurchaseReturn::where('purchase_order_id', $value)
                ->when($this->isEditing && $this->purchaseReturn, function($query) {
                    $query->where('id', '!=', $this->purchaseReturn->id);
                })
                ->with('items')
                ->get();
            
            // Calculate already returned quantities per item
            $alreadyReturned = [];
            foreach ($previousReturns as $return) {
                foreach ($return->items as $returnItem) {
                    $itemId = $returnItem->inventory_item_id;
                    $alreadyReturned[$itemId] = ($alreadyReturned[$itemId] ?? 0) + (float)$returnItem->quantity;
                }
            }
            
            // Pre-populate items from purchase with cumulative tracking
            $this->items = $po->items->filter(function($item) {
                return ($item->quantity ?? 0) > 0;
            })->map(function ($item) use ($po, $alreadyReturned) {
                $purchasedQty = (float)($item->quantity ?? 0);
                $alreadyReturnedQty = $alreadyReturned[$item->inventory_item_id] ?? 0;
                
                // Get current stock at purchase location
                $stock = InventoryStock::where('inventory_item_id', $item->inventory_item_id)
                    ->where('location_id', $po->location_id)
                    ->first();
                $availableStock = $stock ? (float)$stock->quantity : 0;
                
                // Max = min(purchased - already_returned, available_stock)
                $maxQuantity = min(
                    max(0, $purchasedQty - $alreadyReturnedQty),
                    $availableStock
                );
                
                return [
                    'inventoryItemId' => $item->inventory_item_id,
                    'quantity' => null,
                    'maxQuantity' => $maxQuantity,
                    'purchasedQuantity' => $purchasedQty,
                    'alreadyReturned' => $alreadyReturnedQty,
                    'availableStock' => $availableStock,
                    'unitPrice' => $item->unit_price ?? 0,
                    'subtotal' => 0,
                ];
            })->filter(function($item) {
                // Only show items that can still be returned
                return $item['maxQuantity'] > 0;
            })->values()->toArray();
        }
    } else {
        $this->items = [];
    }
}

    public function updatedSupplierId($value)
    {
        // If supplier changed, clear items that don't belong to this supplier
        if ($value && $this->purchaseOrderId) {
            $po = PurchaseOrder::find($this->purchaseOrderId);
            if ($po && $po->supplier_id != $value) {
                $this->purchaseOrderId = null;
                $this->items = [];
            }
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'inventoryItemId' => '',
            'quantity' => null,
            'unitPrice' => 0,
            'subtotal' => 0,
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotal();
    }

    public function calculateSubtotal($index)
    {
        if (isset($this->items[$index])) {
            $item = $this->items[$index];
            $quantity = !empty($item['quantity']) ? (float)$item['quantity'] : 0;
            $unitPrice = !empty($item['unitPrice']) ? (float)$item['unitPrice'] : 0;
            $this->items[$index]['subtotal'] = $quantity * $unitPrice;
            $this->calculateTotal();
        }
    }

    public function calculateTotal()
    {
        // Total is calculated automatically in the save method
    }

    public function fetchUnitPrice($index)
    {
        if (isset($this->items[$index]['inventoryItemId']) && $this->items[$index]['inventoryItemId']) {
            $inventoryItem = InventoryItem::find($this->items[$index]['inventoryItemId']);
            if ($inventoryItem) {
                $this->items[$index]['unitPrice'] = $inventoryItem->unit_purchase_price ?? 0;
                $this->calculateSubtotal($index);
            }
        }
    }

    public function save()
    {
        // Validate basic fields first
        $this->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'returnDate' => 'required|date',
        ]);

        // Filter and validate items that have inventoryItemId selected
        $validItems = [];
        $itemIndexMap = []; // Map original index to validation index

        foreach ($this->items as $originalIndex => $item) {
            // Only process items that have an inventoryItemId selected
            if (!empty($item['inventoryItemId'])) {
                // Convert quantity to float, handling empty strings
                $quantity = '';
                if (isset($item['quantity']) && $item['quantity'] !== '' && $item['quantity'] !== null) {
                    $quantity = (float)$item['quantity'];
                }
                
                // Only add to valid items if quantity is greater than 0
                if ($quantity > 0) {
                    $newIndex = count($validItems);
                    $validItems[] = $item;
                    $itemIndexMap[$originalIndex] = $newIndex;
                }
            }
        }

        if (empty($validItems)) {
            $this->alert('error', 'Please add at least one item with quantity greater than 0.');
            return;
        }

        // Build validation rules only for items that will be saved (have inventoryItemId and quantity > 0)
        $validationRules = [];
        foreach ($this->items as $index => $item) {
            if (!empty($item['inventoryItemId'])) {
                // Check if quantity is valid (not empty and > 0)
                $quantity = '';
                if (isset($item['quantity']) && $item['quantity'] !== '' && $item['quantity'] !== null) {
                    $quantity = (float)$item['quantity'];
                }
                
                // Only validate items that have quantity > 0
                if ($quantity > 0) {
                    $quantityRule = 'required|numeric|min:0.01';
                    if (isset($item['maxQuantity'])) {
                        $quantityRule .= '|max:' . $item['maxQuantity'];
                    }
                    
                    $validationRules["items.{$index}.inventoryItemId"] = 'required|exists:inventory_items,id';
                    $validationRules["items.{$index}.quantity"] = $quantityRule;
                    $validationRules["items.{$index}.unitPrice"] = 'required|numeric|min:0';
                }
            }
        }

        // Validate items
        if (!empty($validationRules)) {
            $this->validate($validationRules);
        }

        // Use only valid items for saving
        $this->items = $validItems;

        DB::transaction(function () {
            if ($this->isEditing) {
                $return = $this->purchaseReturn;
                
                // Prevent status from being changed to 'completed' via save()
                // Status can only be changed via processReturn() method
                $statusToSave = ($this->status === 'completed' && $return->status === 'pending') 
                    ? 'pending' 
                    : $return->status; // Keep existing status if already completed
                
                $return->update([
                    'supplier_id' => $this->supplierId,
                    'purchase_order_id' => $this->purchaseOrderId,
                    'return_date' => $this->returnDate,
                    'note' => $this->note,
                    'status' => $statusToSave, // Always preserve or force to pending
                ]);

                // Delete old items
                $return->items()->delete();
            } else {
                // Always create with 'pending' status - cannot be completed on creation
                $return = PurchaseReturn::create([
                    'branch_id' => branch()->id,
                    'supplier_id' => $this->supplierId,
                    'purchase_order_id' => $this->purchaseOrderId,
                    'return_date' => $this->returnDate,
                    'note' => $this->note,
                    'status' => 'pending', // Always 'pending' on creation
                    'added_by' => user()->id,
                ]);
            }

            $totalAmount = 0;

            foreach ($this->items as $item) {
                $quantity = (float)($item['quantity'] ?? 0);
                $unitPrice = (float)($item['unitPrice'] ?? 0);
                $subtotal = $quantity * $unitPrice;
                $totalAmount += $subtotal;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'inventory_item_id' => $item['inventoryItemId'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $return->update(['total_amount' => $totalAmount]);
            // Process immediately if requested
            if (!$this->isEditing && $this->processImmediately) {
                $this->processReturnLogic($return);
                $return->update(['status' => 'completed']);
                $this->alert('success', 'Purchase return processed! Stock has been reduced.');
            } else {
                $this->alert('success', 'Purchase return saved successfully');
            }
        });

        $this->showModal = false;
        $this->isEditing = false;
        $this->dispatch('purchaseReturnSaved');
    }

    protected function processReturnLogic($return)
    {
        $po = PurchaseOrder::find($return->purchase_order_id);
        $locationId = $po ? $po->location_id : null;
        $location = $locationId ? \Modules\Inventory\Entities\PurchaseLocation::find($locationId) : null;
        $targetBranchId = ($location && $location->type === 'branch' && $location->branch_id)
            ? (int) $location->branch_id
            : ($po ? (int) $po->branch_id : (int) branch()->id);
        
        foreach ($return->items as $item) {
            $quantity = (float)$item->quantity;
            
            // Reduce stock
            if ($locationId) {
                $stock = InventoryStock::where('inventory_item_id', $item->inventory_item_id)
                    ->where('location_id', $locationId)
                    ->first();
                
                if ($stock) {
                    $stock->decrement('quantity', $quantity);
                }
            }
            
            // Create movement record
            InventoryMovement::create([
                'branch_id' => $targetBranchId,
                'location_id' => $locationId,
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $quantity,
                'transaction_type' => 'out',
                'unit_purchase_price' => $item->unit_price,
                'supplier_id' => $return->supplier_id,
                'added_by' => auth()->id(),
            ]);
        }
    }

    public function processReturn()
    {
        if (!$this->isEditing || !$this->purchaseReturn) {
            return;
        }

        try {
            DB::transaction(function () {
                // Lock the purchase return row to prevent concurrent processing
                $return = PurchaseReturn::where('id', $this->purchaseReturn->id)
                    ->lockForUpdate()
                    ->first();

                if (!$return) {
                    DB::rollBack();
                    $this->alert('error', 'Purchase return not found.');
                    return;
                }

                // Re-check status INSIDE transaction to prevent race conditions
                if ($return->status === 'completed') {
                    DB::rollBack();
                    $this->alert('error', 'This purchase return has already been processed.');
                    return;
                }

                // Reload items to ensure we have fresh data
                $return->load('items');

                // Process the return using shared logic
                $this->processReturnLogic($return);

                // Mark return as completed LAST inside transaction to prevent double processing
                $return->update(['status' => 'completed']);
                
                // Refresh the instance
                $this->purchaseReturn = $return->fresh();
            });
        } catch (\Exception $e) {
            \Log::error('Purchase Return Processing Error: ' . $e->getMessage());
            $this->alert('error', 'An error occurred while processing the return: ' . $e->getMessage());
            return;
        }

        // Refresh after transaction completes
        $this->purchaseReturn->refresh();
        
        // Verify it was actually processed
        if ($this->purchaseReturn->status === 'completed') {
            $this->alert('success', 'Purchase return processed successfully. Stock has been reduced.');
            $this->dispatch('purchaseReturnSaved');
        } else {
            $this->alert('error', 'Failed to process purchase return. Please try again.');
        }
    }

    public function render()
    {
        $inventoryItems = InventoryItem::with(['unit', 'category'])
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $categoryName = $item->category ? $item->category->name : 'No Category';
                $unitSymbol = $item->unit ? $item->unit->symbol : '';
                $item->display_name = "{$item->name} ({$categoryName} - {$unitSymbol})";
                return $item;
            });

        $purchaseOrders = PurchaseOrder::where('branch_id', branch()->id)
            ->whereIn('status', ['received', 'partially_received'])
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->with('supplier')
            ->orderBy('po_number')
            ->get();

        return view('inventory::livewire.purchase-return.manage-purchase-return', [
            'suppliers' => Supplier::where('restaurant_id', restaurant()->id)
                ->orderBy('name')
                ->get(),
            'purchaseOrders' => $purchaseOrders,
            'inventoryItems' => $inventoryItems,
            'totalAmount' => collect($this->items)->sum(function($item) {
                // Safely convert to float, handling empty strings
                $quantity = isset($item['quantity']) && $item['quantity'] !== '' && $item['quantity'] !== null 
                    ? (float)$item['quantity'] 
                    : 0;
                $unitPrice = isset($item['unitPrice']) && $item['unitPrice'] !== '' && $item['unitPrice'] !== null 
                    ? (float)$item['unitPrice'] 
                    : 0;
                return $quantity * $unitPrice;
            }),
        ]);
    }
}

