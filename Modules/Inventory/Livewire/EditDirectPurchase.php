<?php

namespace Modules\Inventory\Livewire;

use Livewire\Component;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\BranchPaymentAccountSetting;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditDirectPurchase extends Component
{
    use LivewireAlert;

    public $purchaseId;
    
    // Main form fields
    public $supplierId;
    public $orderDate;
    public $location_id;
    public $status = 'ordered';
    public $notes;
    public $discount = 0;
    public $discount_type = 'fixed';
    
    // Items
    public $items = [];
    public $searchItem = '';
    public $filteredItems = [];
    public $showSearchResults = false;
    
    // Payment fields
    public $recordPayment = false;
    public $paymentAmount;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentAccountId;
    public $paymentNote;
    
    // Readonly data
    public $suppliers = [];
    public $inventoryItems = [];
    public $locations = [];
    public $paymentMethods = ['cash', 'card', 'bank_transfer', 'cheque', 'other'];
        public $paymentAccounts = [];
    public $purchase = null;

    protected $rules = [
        'supplierId' => 'required|exists:suppliers,id',
        'orderDate' => 'required|date',
        'location_id' => 'required|exists:purchase_locations,id',
        'status' => 'required|in:ordered,pending,received,cancelled',
        'discount' => 'nullable|numeric|min:0',
        'discount_type' => 'required|in:fixed,percentage',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.discount' => 'nullable|numeric|min:0',
        'items.*.discount_type' => 'required|in:fixed,percentage',
        'paymentAmount' => 'nullable|numeric|min:0',
        'paymentDate' => 'required_if:recordPayment,true|date',
        'paymentMethod' => 'required_if:recordPayment,true',
        'paymentAccountId' => 'nullable|exists:payment_accounts,id',
    ];

    public function mount($purchaseId)
    {
        $this->purchaseId = $purchaseId;
        $this->loadPurchase();
        $this->loadData();
    }

    public function loadPurchase()
    {
        $this->purchase = PurchaseOrder::with('items')->findOrFail($this->purchaseId);
        
        $this->supplierId = $this->purchase->supplier_id;
        $this->orderDate = $this->purchase->order_date->format('Y-m-d');
        $this->location_id = $this->purchase->location_id;
        $this->status = $this->purchase->status;
        $this->notes = $this->purchase->notes;
        $this->discount = $this->purchase->discount ?? 0;
        $this->discount_type = $this->purchase->discount_type ?? 'fixed';
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        
        // Load items
        $this->items = $this->purchase->items->map(function ($item) {
            return [
                '_key' => 'po_item_' . $item->id,
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'discount_type' => $item->discount_type ?? 'fixed',
            ];
        })->toArray();
    }

    public function loadData()
    {
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->locations = PurchaseLocation::orderBy('name')->get();
        $this->inventoryItems = InventoryItem::orderBy('name')->get();
            $this->loadPaymentAccounts();
        }

        public function loadPaymentAccounts()
        {
            try {
                // Payment accounts are branch-scoped in this app
                $this->paymentAccounts = PaymentAccount::where('branch_id', branch()->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            } catch (\Exception $e) {
                $this->paymentAccounts = [];
            }
    }

    public function updatedPaymentMethod($value)
    {
        // Auto-select default payment account for this payment method
        if ($value && !$this->paymentAccountId) {
            $defaultAccount = BranchPaymentAccountSetting::getDefaultAccount(branch()->id, $value);
            if ($defaultAccount) {
                $this->paymentAccountId = $defaultAccount->id;
            }
        }
    }

    public function addItem()
    {
        $this->items[] = [
            '_key' => (string) Str::uuid(),
            'inventory_item_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'discount_type' => 'fixed',
        ];
    }

    public function searchItems()
    {
        $this->showSearchResults = false;

        $term = trim((string) $this->searchItem);
        if ($term === '') {
            $this->filteredItems = [];
            return;
        }

        $this->filteredItems = InventoryItem::query()
            ->select(['id', 'name', 'unit_purchase_price'])
            ->where('restaurant_id', restaurant()->id)
            ->where('name', 'like', '%' . $term . '%')
            ->limit(10)
            ->get();
        
        $this->showSearchResults = true;
    }

    public function updatedSearchItem()
    {
        $this->searchItems();
    }

    public function selectItem($itemId)
    {
        $item = InventoryItem::find($itemId);
        
        if ($item) {
            $targetIndex = null;
            foreach ($this->items as $index => $row) {
                if (empty($row['inventory_item_id'])) {
                    $targetIndex = $index;
                    break;
                }
            }

            if ($targetIndex === null) {
                $targetIndex = count($this->items);
                $this->items[] = [
                    '_key' => (string) Str::uuid(),
                    'inventory_item_id' => '',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                ];
            }

            if (empty($this->items[$targetIndex]['_key'])) {
                $this->items[$targetIndex]['_key'] = (string) Str::uuid();
            }

            $this->items[$targetIndex]['inventory_item_id'] = $itemId;
            $this->items[$targetIndex]['quantity'] = (float) ($this->items[$targetIndex]['quantity'] ?? 0) > 0
                ? $this->items[$targetIndex]['quantity']
                : 1;

            if (!isset($this->items[$targetIndex]['unit_price']) || (float) $this->items[$targetIndex]['unit_price'] <= 0) {
                $this->items[$targetIndex]['unit_price'] = $item->unit_purchase_price ?? 0;
            }

            if (!isset($this->items[$targetIndex]['discount'])) {
                $this->items[$targetIndex]['discount'] = 0;
            }
            if (empty($this->items[$targetIndex]['discount_type'])) {
                $this->items[$targetIndex]['discount_type'] = 'fixed';
            }
            
            // Clear search
            $this->searchItem = '';
            $this->filteredItems = [];
            $this->showSearchResults = false;
        }
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        
        // Ensure at least one item
        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function updateItemPrice($index)
    {
        if (isset($this->items[$index]['inventory_item_id']) && $this->items[$index]['inventory_item_id']) {
            $item = InventoryItem::find($this->items[$index]['inventory_item_id']);
            if ($item && $item->unit_purchase_price !== null) {
                $this->items[$index]['unit_price'] = $item->unit_purchase_price;
            }
        }
    }

    public function getItemSubtotalProperty()
    {
        return collect($this->items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $lineTotal = $qty * $price;
            
            // Apply item-level discount
            $itemDiscount = ($item['discount_type'] ?? 'fixed') === 'percentage'
                ? $lineTotal * (((float)($item['discount'] ?? 0)) / 100)
                : ((float)($item['discount'] ?? 0));
            
            return max(0, $lineTotal - $itemDiscount);
        });
    }

    public function getDiscountAmountProperty()
    {
        if (!$this->discount) {
            return 0;
        }
        
        if ($this->discount_type === 'percentage') {
            return ($this->itemSubtotal * $this->discount) / 100;
        }
        
        return $this->discount;
    }

    public function getFinalTotalProperty()
    {
        return max(0, $this->itemSubtotal - $this->discountAmount);
    }
    
    protected function updateInventoryStock()
    {
        // Get the purchase location
        $location = PurchaseLocation::find($this->location_id);
        
        if (!$location) {
            throw new \Exception('Purchase location not found');
        }
        
        // Use branch_id from location (for branch-type locations) or the current branch
        $branchId = $location->type === 'branch' && $location->branch_id 
            ? $location->branch_id 
            : branch()->id;
        
        foreach ($this->items as $item) {
            $inventoryItemId = $item['inventory_item_id'];
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            
            // Update inventory stock with location_id
            $stock = InventoryStock::firstOrCreate(
                [
                    'inventory_item_id' => $inventoryItemId,
                    'branch_id' => $branchId,
                    'location_id' => $this->location_id,
                ],
                [
                    'quantity' => 0
                ]
            );
            
            // Increase stock quantity
            $stock->increment('quantity', $quantity);
            
            // Create inventory movement record for audit trail
            InventoryMovement::create([
                'branch_id' => $branchId,
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'transaction_type' => 'in', // 'in' for incoming stock from purchase
                'unit_purchase_price' => $unitPrice,
                'supplier_id' => $this->supplierId,
                'added_by' => auth()->id(),
            ]);
        }
    }
    
    protected function updateSupplierMetrics()
    {
        // Update supplier's last purchase date and total purchase value
        $supplier = Supplier::find($this->supplierId);
        if ($supplier) {
            $supplier->update([
                'last_purchase_date' => now(),
                'total_purchase_value' => $supplier->orders()
                    ->where('status', 'received')
                    ->sum('total_amount'),
            ]);
        }
    }

    public function updatePurchase()
    {
        // Validate payment amount doesn't exceed total
        if ($this->recordPayment && $this->paymentAmount) {
            $total = $this->finalTotal;
            $alreadyPaid = $this->purchase->payments()->sum('amount');
            $due = max(0, $total - $alreadyPaid);
            
            if ($this->paymentAmount > $due) {
                $this->addError('paymentAmount', 'Payment amount cannot exceed the due amount (' . currency_format($due, restaurant()->currency_id) . ')');
                return;
            }
        }

        $this->validate();

        DB::transaction(function () {
            $previousStatus = $this->purchase->status;
            
            // Update purchase
            $this->purchase->update([
                'supplier_id' => $this->supplierId,
                'location_id' => $this->location_id,
                'order_date' => $this->orderDate,
                'total_amount' => $this->itemSubtotal,
                'discount' => $this->discount,
                'discount_type' => $this->discount_type,
                'status' => $this->status,
                'notes' => $this->notes,
            ]);

            // Delete existing items and create new ones
            $this->purchase->items()->delete();
            
            foreach ($this->items as $item) {
                // Create/recreate all items
                $qty = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                $subtotal = $qty * $price;
                
                $this->purchase->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? 'fixed',
                ]);
            }
            
            // If status changed to 'received', update inventory stock and movements
            if ($previousStatus !== 'received' && $this->status === 'received') {
                $this->updateInventoryStock();
                $this->updateSupplierMetrics();
            }
            
            // Record payment if requested
            if ($this->recordPayment && $this->paymentAmount && $this->paymentAmount > 0) {
                $paidOn = $this->paymentDate ?: now();
                $paymentData = [
                    'purchase_order_id' => $this->purchase->id,
                    'supplier_id' => $this->supplierId,
                    'amount' => $this->paymentAmount,
                    'paid_on' => $paidOn,
                    'payment_method' => $this->paymentMethod,
                    'note' => $this->paymentNote,
                    'added_by' => user()->id,
                ];
                
                if ($this->paymentAccountId) {
                    $paymentData['payment_account_id'] = $this->paymentAccountId;
                }
                
                $payment = SupplierPayment::create($paymentData);

                // Update Payment Account Balance and log transaction if account selected
                if ($this->paymentAccountId) {
                    $account = PaymentAccount::find($this->paymentAccountId);
                    if ($account) {
                        $account->decrement('current_balance', $this->paymentAmount);

                        // Log Transaction (credit = money out for purchase payment)
                        AccountTransaction::create([
                            'payment_account_id' => $account->id,
                            'amount' => $this->paymentAmount,
                            'type' => 'credit', // Money Out
                            'reference_type' => get_class($payment),
                            'reference_id' => $payment->id,
                            'description' => 'Payment for Purchase Order: ' . $this->purchase->po_number . ($this->paymentNote ? ' - ' . $this->paymentNote : ''),
                            'transaction_date' => $paidOn,
                        ]);
                    }
                }
            }
        });

        $this->alert('success', 'Purchase updated successfully!');
        return redirect()->route('purchases.index');
    }

    public function render()
    {
        return view('inventory::livewire.edit-direct-purchase', [
            'itemSubtotal' => $this->itemSubtotal,
            'discountAmount' => $this->discountAmount,
            'finalTotal' => $this->finalTotal,
        ]);
    }
}
