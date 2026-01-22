<?php

namespace Modules\Inventory\Livewire;

use Livewire\Component;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\BranchPaymentAccountSetting;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDirectPurchase extends Component
{
    use LivewireAlert;

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
    
    // Payment fields (optional)
    public $recordPayment = false;
    public $paymentAmount;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentAccountId;
    public $paymentNote;
    public $paymentAccounts = [];
    
    // Readonly data
    public $suppliers = [];
    public $inventoryItems = [];
    public $locations = [];
    public $paymentMethods = ['cash', 'card', 'bank_transfer', 'cheque', 'other'];

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
        'items.*.discount_type' => 'nullable|in:fixed,percentage',
        'paymentAmount' => 'nullable|numeric|min:0.01',
        'paymentDate' => 'nullable|date_format:Y-m-d\TH:i',
        'paymentMethod' => 'nullable|in:cash,card,bank_transfer,cheque,other',
        'paymentAccountId' => 'nullable|exists:payment_accounts,id',
        'paymentNote' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'supplierId.required' => 'Supplier is required',
        'supplierId.exists' => 'Selected supplier does not exist',
        'orderDate.required' => 'Order date is required',
        'orderDate.date' => 'Order date must be a valid date',
        'location_id.required' => 'Location is required',
        'location_id.exists' => 'Selected location does not exist',
        'status.required' => 'Status is required',
        'status.in' => 'Status must be one of: ordered, pending, received, cancelled',
        'discount.numeric' => 'Discount must be a valid number',
        'discount.min' => 'Discount cannot be negative',
        'discount_type.required' => 'Discount type is required',
        'discount_type.in' => 'Discount type must be either fixed or percentage',
        'items.required' => 'At least one item is required',
        'items.min' => 'At least one item is required',
        'items.*.inventory_item_id.required' => 'Item is required for each line',
        'items.*.inventory_item_id.exists' => 'Selected item does not exist',
        'items.*.quantity.required' => 'Quantity is required for each item',
        'items.*.quantity.numeric' => 'Quantity must be a valid number',
        'items.*.quantity.min' => 'Quantity must be at least 0.01',
        'items.*.unit_price.required' => 'Unit price is required for each item',
        'items.*.unit_price.numeric' => 'Unit price must be a valid number',
        'items.*.unit_price.min' => 'Unit price cannot be negative',
        'paymentAmount.numeric' => 'Payment amount must be a valid number',
        'paymentAmount.min' => 'Payment amount must be at least 0.01',
        'paymentDate.date_format' => 'Payment date must be a valid date and time',
        'paymentMethod.in' => 'Payment method is invalid',
    ];

    public function mount()
    {
        $this->orderDate = now()->format('Y-m-d');
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        
        // Load data
        $this->loadSuppliers();
        $this->loadInventoryItems();
        $this->loadLocations();
        $this->loadPaymentAccounts();
        
        // Add first item
        $this->addItem();
    }

    public function loadSuppliers()
    {
        $this->suppliers = Supplier::where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function loadInventoryItems()
    {
        $this->inventoryItems = InventoryItem::with(['unit', 'category'])
            ->orderBy('name')
            ->get();
    }

    public function loadLocations()
    {
        $this->locations = PurchaseLocation::getForRestaurant(restaurant()->id);
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

    public function savePurchase()
    {
        // Validate payment amount doesn't exceed total before standard validation
        if ($this->recordPayment && $this->paymentAmount) {
            $total = $this->finalTotal;
            if ($this->paymentAmount > $total) {
                $this->addError('paymentAmount', 'Payment amount cannot exceed the total amount (' . currency_format($total, restaurant()->currency_id) . ')');
                return;
            }
        }

        $this->validate();

        DB::transaction(function () {
            // Create purchase
            $purchase = PurchaseOrder::create([
                'po_number' => $this->generatePurchaseNumber(),
                'branch_id' => branch()->id,
                'supplier_id' => $this->supplierId,
                'location_id' => $this->location_id,
                'order_date' => $this->orderDate,
                'total_amount' => $this->itemSubtotal,
                'discount' => (float) ($this->discount ?? 0),
                'discount_type' => $this->discount_type,
                'status' => $this->status,
                'notes' => $this->notes,
                'created_by' => user()->id,
            ]);

            // Create items
            foreach ($this->items as $item) {
                $purchase->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'discount' => (float) ($item['discount'] ?? 0),
                    'discount_type' => $item['discount_type'] ?? 'fixed',
                    'received_quantity' => $this->status === 'received' ? $item['quantity'] : 0,
                ]);
            }

            // If status is 'received', create inventory movements immediately
            if ($this->status === 'received') {
                $this->createInventoryMovements($purchase);
            }

            // Record payment if provided
            if ($this->recordPayment && $this->paymentAmount > 0) {
                $this->recordPaymentForPurchase($purchase);
            }
        });

        $this->alert('success', 'Purchase created successfully');
        return redirect()->route('purchases.index');
    }

    private function createInventoryMovements(PurchaseOrder $purchase)
    {
        $location = $purchase->location;
        
        if (!$location) {
            throw new \Exception('Purchase location not found');
        }
        
        $targetBranchId = $location->type === 'branch' && $location->branch_id 
            ? $location->branch_id 
            : $purchase->branch_id;

        foreach ($purchase->items as $item) {
            $quantity = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;
            
            // Update stock with location_id
            $stock = InventoryStock::firstOrCreate(
                [
                    'inventory_item_id' => $item->inventory_item_id,
                    'branch_id' => $targetBranchId,
                    'location_id' => $purchase->location_id,
                ],
                ['quantity' => 0]
            );
            $stock->increment('quantity', $quantity);
            
            // Create inventory movement record
            InventoryMovement::create([
                'branch_id' => $targetBranchId,
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $quantity,
                'transaction_type' => 'in',
                'unit_purchase_price' => $unitPrice,
                'supplier_id' => $purchase->supplier_id,
                'added_by' => auth()->id(),
            ]);
        }
        
        // Update supplier metrics
        $this->updateSupplierMetrics($purchase->supplier_id);
    }
    
    private function updateSupplierMetrics($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $supplier->update([
                'last_purchase_date' => now(),
            ]);
        }
    }

    private function recordPaymentForPurchase(PurchaseOrder $purchase)
    {
        $paidOn = $this->paymentDate ?: now();
        $paymentData = [
            'supplier_id' => $purchase->supplier_id,
            'purchase_order_id' => $purchase->id,
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
                    'description' => 'Payment for Purchase Order: ' . $purchase->po_number . ($this->paymentNote ? ' - ' . $this->paymentNote : ''),
                    'transaction_date' => $paidOn,
                ]);
            }
        }
    }

    private function generatePurchaseNumber()
    {
        $year = date('Y');
        $branchId = branch()->id;
        $prefix = 'PUR-' . $branchId . '-' . $year . '-';

        $lastPurchase = PurchaseOrder::where('po_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchase) {
            $lastSequence = (int) substr($lastPurchase->po_number, strlen($prefix));
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return $prefix . $sequence;
    }

    public function render()
    {
        return view('inventory::livewire.create-direct-purchase', [
            'itemSubtotal' => $this->itemSubtotal,
            'discountAmount' => $this->discountAmount,
            'finalTotal' => $this->finalTotal,
        ]);
    }
}
