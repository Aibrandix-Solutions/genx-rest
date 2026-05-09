<?php

namespace Modules\Inventory\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderItem;
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
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\PurchaseItemsImportTemplateExport;
use Modules\Inventory\Entities\PurchaseAttachment;

class EditDirectPurchase extends Component
{
    use WithFileUploads, LivewireAlert;

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
    public $itemImportFile;
    public $searchItem = '';
    public $filteredItems = [];
    public $showSearchResults = false;

    // Quick-add new inventory item
    public $showQuickAddModal = false;
    public $quickAddName = '';
    public $quickAddCategoryId = '';
    public $quickAddUnitId = '';
    public $quickAddPrice = 0;
    public $quickAddThresholdQuantity = 0;
    public $quickAddSaving = false;
    
    // Payment fields
    public $recordPayment = false;
    public $paymentAmount;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentAccountId;
    public $paymentNote;
    public $editingPaymentId = null;
    public $existingPayments = [];
    
    // Readonly data
    public $suppliers = [];
    public $inventoryItems = [];
    public $itemCategories = [];
    public $units = [];
    public $locations = [];
    public $paymentMethods = ['cash', 'card', 'bank_transfer', 'cheque', 'other'];
    public $paymentAccounts = [];
    public $purchase = null;

    // Attachments
    public $attachments = [];        // new files to upload
    public $existingAttachments = []; // already saved attachments

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
        'paymentDate' => 'nullable|required_if:recordPayment,true|date_format:Y-m-d\TH:i',
        'paymentMethod' => 'required_if:recordPayment,true',
        'paymentAccountId' => 'nullable|exists:payment_accounts,id',
        'attachments.*' => 'nullable|file|mimes:pdf,jpeg,jpg,png,gif,webp|max:5120',
        'itemImportFile' => 'nullable|file|mimes:xlsx,xls,csv,txt|max:5120',
    ];

    public function mount($purchaseId)
    {
        $this->purchaseId = $purchaseId;
        $this->loadPurchase();
        $this->loadData();
    }

    public function loadPurchase()
    {
        $this->purchase = PurchaseOrder::with('items', 'attachments', 'payments.account')->findOrFail($this->purchaseId);
        
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
                'last_purchase_price' => PurchaseOrderItem::where('inventory_item_id', $item->inventory_item_id)
                    ->where('id', '!=', $item->id)
                    ->orderBy('created_at', 'desc')
                    ->value('unit_price'),
            ];
        })->toArray();

        // Load existing attachments
        $this->existingAttachments = $this->purchase->attachments->map(function ($att) {
            return [
                'id'            => $att->id,
                'original_name' => $att->original_name,
                'file_type'     => $att->file_type,
                'url'           => $att->url,
                'mime_type'     => $att->mime_type,
            ];
        })->toArray();

        $this->refreshExistingPayments();
    }

    protected function refreshExistingPayments(): void
    {
        $this->existingPayments = $this->purchase->payments()
            ->with('account')
            ->orderByDesc('paid_on')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'paid_on' => optional($payment->paid_on)->format('Y-m-d H:i:s'),
                    'payment_method' => $payment->payment_method,
                    'payment_account_id' => $payment->payment_account_id,
                    'payment_account_name' => $payment->account?->name,
                    'note' => $payment->note,
                ];
            })
            ->toArray();
    }

    public function startEditPayment($paymentId): void
    {
        $payment = $this->purchase->payments()->find($paymentId);

        if (!$payment) {
            $this->alert('error', 'Payment not found.');
            return;
        }

        $this->recordPayment = true;
        $this->editingPaymentId = $payment->id;
        $this->paymentAmount = (float) $payment->amount;
        $this->paymentDate = optional($payment->paid_on)->format('Y-m-d\TH:i');
        $this->paymentMethod = $payment->payment_method ?: 'cash';
        $this->paymentAccountId = $payment->payment_account_id;
        $this->paymentNote = $payment->note;
    }

    public function cancelEditPayment(): void
    {
        $this->editingPaymentId = null;
        $this->paymentAmount = null;
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethod = 'cash';
        $this->paymentAccountId = null;
        $this->paymentNote = null;
    }

    public function loadData()
    {
        $this->suppliers = Supplier::where('restaurant_id', restaurant()->id)->orderBy('name')->get();
        $this->locations = PurchaseLocation::getForRestaurant(restaurant()->id);
        $this->inventoryItems = InventoryItem::where('restaurant_id', restaurant()->id)->orderBy('name')->get();
        $this->itemCategories = InventoryItemCategory::orderBy('name')->get();
        $this->units = Unit::orderBy('name')->get();
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
        $this->items[] = $this->makePurchaseItemRow();
    }

    public function downloadItemsImportTemplate()
    {
        return Excel::download(new PurchaseItemsImportTemplateExport(), 'purchase-items-template.xlsx');
    }

    public function importItemsFromFile(): void
    {
        $this->validateOnly('itemImportFile');

        if (!$this->itemImportFile) {
            return;
        }

        $rows = Excel::toArray([], $this->itemImportFile)[0] ?? [];
        if (count($rows) < 2) {
            $this->addError('itemImportFile', 'Template appears empty. Please add at least one row.');
            return;
        }

        $headers = array_map(
            fn ($h) => strtolower(trim((string) $h)),
            (array) ($rows[0] ?? [])
        );
        $requiredHeaders = ['item_name', 'quantity'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headers, true)) {
                $this->addError('itemImportFile', "Missing required column: {$requiredHeader}");
                return;
            }
        }

        $indexMap = array_flip($headers);
        $errors = [];
        $importedCount = 0;
        $this->items = array_values(array_filter($this->items, fn ($row) => !empty($row['inventory_item_id'])));

        for ($i = 1; $i < count($rows); $i++) {
            $row = (array) $rows[$i];
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $itemName = trim((string) ($row[$indexMap['item_name']] ?? ''));
            $quantityRaw = $row[$indexMap['quantity']] ?? null;
            $unitPriceRaw = $row[$indexMap['unit_price']] ?? null;
            $discountRaw = $row[$indexMap['discount']] ?? 0;
            $discountTypeRaw = strtolower(trim((string) ($row[$indexMap['discount_type']] ?? 'fixed')));
            $excelRow = $i + 1;

            if ($itemName === '') {
                $errors[] = "Row {$excelRow}: item_name is required.";
                continue;
            }

            $quantity = is_numeric($quantityRaw) ? (float) $quantityRaw : null;
            if ($quantity === null || $quantity <= 0) {
                $errors[] = "Row {$excelRow}: quantity must be greater than 0.";
                continue;
            }

            $item = InventoryItem::query()
                ->where('restaurant_id', restaurant()->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($itemName)])
                ->first();

            if (!$item) {
                $errors[] = "Row {$excelRow}: item '{$itemName}' not found.";
                continue;
            }

            $unitPrice = is_numeric($unitPriceRaw) ? (float) $unitPriceRaw : (float) ($item->unit_purchase_price ?? 0);
            $discount = is_numeric($discountRaw) ? (float) $discountRaw : 0;
            $discountType = in_array($discountTypeRaw, ['fixed', 'percentage'], true) ? $discountTypeRaw : 'fixed';

            $this->items[] = [
                ...$this->makePurchaseItemRow(),
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'unit_price' => max(0, $unitPrice),
                'discount' => max(0, $discount),
                'discount_type' => $discountType,
                'last_purchase_price' => PurchaseOrderItem::where('inventory_item_id', $item->id)
                    ->orderBy('created_at', 'desc')
                    ->value('unit_price'),
            ];
            $importedCount++;
        }

        $this->itemImportFile = null;

        if (!empty($errors)) {
            $this->addError('itemImportFile', implode(' ', array_slice($errors, 0, 5)));
        }

        if ($importedCount > 0) {
            $this->alert('success', "{$importedCount} item rows imported.");
        } elseif (empty($errors)) {
            $this->addError('itemImportFile', 'No rows were imported.');
        }
    }

    protected function makePurchaseItemRow(): array
    {
        return [
            '_key' => (string) Str::uuid(),
            'inventory_item_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'discount_type' => 'fixed',
            'last_purchase_price' => null,
        ];
    }

    public function openQuickAddModal()
    {
        $this->quickAddName = $this->searchItem;
        $this->quickAddCategoryId = '';
        $this->quickAddUnitId = '';
        $this->quickAddPrice = 0;
        $this->quickAddThresholdQuantity = 0;
        $this->showQuickAddModal = true;
        $this->showSearchResults = false;
    }

    public function closeQuickAddModal()
    {
        $this->showQuickAddModal = false;
        $this->reset(['quickAddName', 'quickAddCategoryId', 'quickAddUnitId', 'quickAddPrice', 'quickAddThresholdQuantity', 'quickAddSaving']);
    }

    public function saveQuickAddItem()
    {
        if ($this->quickAddSaving) {
            return;
        }

        $this->quickAddSaving = true;

        $this->validateOnly('quickAddName', ['quickAddName' => 'required|string|max:255']);
        $this->validateOnly('quickAddCategoryId', ['quickAddCategoryId' => 'required|exists:inventory_item_categories,id']);
        $this->validateOnly('quickAddUnitId', ['quickAddUnitId' => 'required|exists:units,id']);
        $this->validateOnly('quickAddPrice', ['quickAddPrice' => 'required|numeric|min:0']);
        $this->validateOnly('quickAddThresholdQuantity', ['quickAddThresholdQuantity' => 'required|numeric|min:0']);

        try {
            $item = InventoryItem::create([
                'name' => $this->quickAddName,
                'restaurant_id' => restaurant()->id,
                'inventory_item_category_id' => $this->quickAddCategoryId,
                'unit_id' => $this->quickAddUnitId,
                'threshold_quantity' => $this->quickAddThresholdQuantity,
                'unit_purchase_price' => $this->quickAddPrice,
            ]);

            $this->loadData();
            $this->items[] = [
                ...$this->makePurchaseItemRow(),
                'inventory_item_id' => $item->id,
                'unit_price' => $item->unit_purchase_price ?? 0,
                'last_purchase_price' => null,
            ];

            $this->searchItem = '';
            $this->filteredItems = [];
            $this->showSearchResults = false;
            $this->showQuickAddModal = false;
            $this->reset(['quickAddName', 'quickAddCategoryId', 'quickAddUnitId', 'quickAddPrice', 'quickAddThresholdQuantity']);
            $this->alert('success', 'Item "' . $item->name . '" created and added.');
        } finally {
            $this->quickAddSaving = false;
        }
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
            ->get()
            ->map(function ($item) {
                $item->last_purchase_price = PurchaseOrderItem::where('inventory_item_id', $item->id)
                    ->orderBy('created_at', 'desc')
                    ->value('unit_price');
                return $item;
            });

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
                $this->items[] = $this->makePurchaseItemRow();
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

            // Store the last purchased price for info display
            $this->items[$targetIndex]['last_purchase_price'] = PurchaseOrderItem::where('inventory_item_id', $itemId)
                ->orderBy('created_at', 'desc')
                ->value('unit_price');
            
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
            $itemId = $this->items[$index]['inventory_item_id'];
            $item = InventoryItem::find($itemId);
            if ($item && $item->unit_purchase_price !== null) {
                $this->items[$index]['unit_price'] = $item->unit_purchase_price;
            }
            $this->items[$index]['last_purchase_price'] = PurchaseOrderItem::where('inventory_item_id', $itemId)
                ->orderBy('created_at', 'desc')
                ->value('unit_price');
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
                'location_id' => $this->location_id,
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

    /**
     * Reconcile inventory stock after editing an already-received purchase.
     *
     * Computes per-item quantity deltas (and handles location changes) and
     * creates correcting InventoryMovement records so the audit trail stays clean.
     *
     * @param array $oldItemsSnap  Keyed by inventory_item_id: ['quantity' => float, 'unit_price' => float]
     * @param int   $oldLocationId The location_id that was saved before the edit
     */
    protected function reconcileReceivedStock(array $oldItemsSnap, int $oldLocationId): void
    {
        $newLocationId   = (int) $this->location_id;
        $locationChanged = $oldLocationId !== $newLocationId;

        $oldLocation = PurchaseLocation::find($oldLocationId);
        $newLocation = PurchaseLocation::find($newLocationId);

        if (!$newLocation) {
            throw new \Exception('Purchase location not found.');
        }

        $oldBranchId = ($oldLocation && $oldLocation->type === 'branch' && $oldLocation->branch_id)
            ? (int) $oldLocation->branch_id
            : branch()->id;

        $newBranchId = ($newLocation->type === 'branch' && $newLocation->branch_id)
            ? (int) $newLocation->branch_id
            : branch()->id;

        // Reload items after the delete-and-recreate to get the freshly saved values
        $newItemsSnap = $this->purchase->fresh()->items->mapWithKeys(fn($i) => [
            $i->inventory_item_id => [
                'quantity'   => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
            ],
        ])->toArray();

        if ($locationChanged) {
            // Location changed: reverse ALL stock from old location, add ALL to new location
            foreach ($oldItemsSnap as $itemId => $old) {
                $this->applyStockMovement(
                    (int) $itemId, $old['quantity'], $old['unit_price'],
                    $oldBranchId, $oldLocationId, 'out'
                );
            }
            foreach ($newItemsSnap as $itemId => $new) {
                $this->applyStockMovement(
                    (int) $itemId, $new['quantity'], $new['unit_price'],
                    $newBranchId, $newLocationId, 'in'
                );
            }
        } else {
            // Same location: compute per-item delta and apply only the difference
            $allItemIds = collect(array_keys($oldItemsSnap))
                ->merge(array_keys($newItemsSnap))
                ->unique();

            foreach ($allItemIds as $itemId) {
                $oldQty    = (float) ($oldItemsSnap[$itemId]['quantity'] ?? 0);
                $newQty    = (float) ($newItemsSnap[$itemId]['quantity'] ?? 0);
                $delta     = $newQty - $oldQty;

                if (abs($delta) < 0.0001) {
                    continue; // no quantity change for this item
                }

                $unitPrice = (float) ($newItemsSnap[$itemId]['unit_price']
                    ?? $oldItemsSnap[$itemId]['unit_price']
                    ?? 0);

                $this->applyStockMovement(
                    (int) $itemId, abs($delta), $unitPrice,
                    $newBranchId, $newLocationId,
                    $delta > 0 ? 'in' : 'out'
                );
            }
        }
    }

    /**
     * Apply a single stock change and record the corresponding InventoryMovement.
     */
    protected function applyStockMovement(
        int    $inventoryItemId,
        float  $qty,
        float  $unitPrice,
        int    $branchId,
        int    $locationId,
        string $type   // 'in' or 'out'
    ): void {
        $stock = InventoryStock::firstOrCreate(
            ['inventory_item_id' => $inventoryItemId, 'branch_id' => $branchId, 'location_id' => $locationId],
            ['quantity' => 0]
        );

        if ($type === 'in') {
            $stock->increment('quantity', $qty);
        } else {
            $stock->decrement('quantity', $qty);
        }

        InventoryMovement::create([
            'branch_id'           => $branchId,
            'location_id'         => $locationId,
            'inventory_item_id'   => $inventoryItemId,
            'quantity'            => $qty,
            'transaction_type'    => $type,
            'unit_purchase_price' => $unitPrice,
            'supplier_id'         => $this->supplierId,
            'added_by'            => auth()->id(),
        ]);
    }

    public function updatePurchase()
    {
        // Validate payment amount doesn't exceed total
        if ($this->recordPayment && $this->paymentAmount) {
            $total = $this->finalTotal;
            $alreadyPaid = (float) $this->purchase->payments()->sum('amount');
            $editablePaymentAmount = 0;

            if ($this->editingPaymentId) {
                $editablePayment = $this->purchase->payments()->find($this->editingPaymentId);
                if (!$editablePayment) {
                    $this->addError('paymentAmount', 'Selected payment was not found for editing.');
                    return;
                }
                $editablePaymentAmount = (float) $editablePayment->amount;
            }

            // When editing, the original amount can be re-used, so add it back to the allowed ceiling.
            $due = max(0, $total - ($alreadyPaid - $editablePaymentAmount));
            
            if ($this->paymentAmount > $due) {
                $this->addError('paymentAmount', 'Payment amount cannot exceed the due amount (' . currency_format($due, restaurant()->currency_id) . ')');
                return;
            }
        }

        // Prevent reverting a received purchase back to a non-received status.
        // Stock has already been applied; use a Purchase Return to adjust stock instead.
        if ($this->purchase->status === 'received' && $this->status !== 'received') {
            $this->addError('status', 'A received purchase cannot be reverted to a different status. Use a Purchase Return to adjust stock.');
            return;
        }

        $this->validate();

        try {
        DB::transaction(function () {
            $previousStatus = $this->purchase->status;
            $oldLocationId  = (int) $this->purchase->location_id;

            // Snapshot existing items BEFORE deletion — needed for stock delta calculation.
            $oldItemsSnap = [];
            if ($previousStatus === 'received') {
                $oldItemsSnap = $this->purchase->items->mapWithKeys(fn($i) => [
                    $i->inventory_item_id => [
                        'quantity'   => (float) $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                    ],
                ])->toArray();
            }

            // Update purchase
            $this->purchase->update([
                'supplier_id' => $this->supplierId,
                'location_id' => $this->location_id,
                'order_date' => $this->orderDate,
                'total_amount' => $this->finalTotal,
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
                    'received_quantity' => $this->status === 'received' ? $qty : 0,
                ]);
            }

            // Stock management based on status transitions
            if ($previousStatus === 'received' && $this->status === 'received') {
                // Already received — auto-apply delta corrections for any quantity/item/location changes
                $this->reconcileReceivedStock($oldItemsSnap, $oldLocationId);
            } elseif ($previousStatus !== 'received' && $this->status === 'received') {
                // Transitioning to received for the first time — add all stock
                $this->updateInventoryStock();
                $this->updateSupplierMetrics();
            }
            
            // Record payment if requested
            if ($this->recordPayment && $this->paymentAmount && $this->paymentAmount > 0) {
                $paidOn = $this->paymentDate ?: now();
                $paymentAccountId = $this->paymentAccountId ?: null;

                if ($this->editingPaymentId) {
                    $payment = SupplierPayment::where('purchase_order_id', $this->purchase->id)
                        ->where('id', $this->editingPaymentId)
                        ->lockForUpdate()
                        ->first();

                    if (!$payment) {
                        throw new \Exception('Payment not found for update.');
                    }

                    $oldAmount = (float) $payment->amount;
                    $oldAccountId = $payment->payment_account_id;

                    // Revert previous account impact before applying new values.
                    if ($oldAccountId) {
                        $oldAccount = PaymentAccount::find($oldAccountId);
                        if (!$oldAccount) {
                            throw new \RuntimeException(
                                trans('inventory::modules.purchaseOrder.payment_account_missing_revert')
                            );
                        }
                        $oldAccount->increment('current_balance', $oldAmount);
                    }

                    // Replace old account transaction logs for this payment.
                    AccountTransaction::where('reference_type', get_class($payment))
                        ->where('reference_id', $payment->id)
                        ->delete();

                    $payment->update([
                        'supplier_id' => $this->supplierId,
                        'payment_account_id' => $paymentAccountId,
                        'amount' => $this->paymentAmount,
                        'paid_on' => $paidOn,
                        'payment_method' => $this->paymentMethod,
                        'note' => $this->paymentNote,
                    ]);
                } else {
                    $paymentData = [
                        'purchase_order_id' => $this->purchase->id,
                        'supplier_id' => $this->supplierId,
                        'amount' => $this->paymentAmount,
                        'paid_on' => $paidOn,
                        'payment_method' => $this->paymentMethod,
                        'note' => $this->paymentNote,
                        'added_by' => user()->id,
                    ];
                    
                    if ($paymentAccountId) {
                        $paymentData['payment_account_id'] = $paymentAccountId;
                    }
                    
                    $payment = SupplierPayment::create($paymentData);
                }

                // Update Payment Account Balance and log transaction if account selected
                if ($paymentAccountId) {
                    $account = PaymentAccount::find($paymentAccountId);
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

                // Refresh payment list and reset editor state after create/update.
                $this->purchase->refresh();
                $this->refreshExistingPayments();
                $this->cancelEditPayment();
            }

            // Save new attachments
            if (!empty($this->attachments)) {
                $dir = public_path('user-uploads/' . PurchaseAttachment::UPLOAD_DIR);
                if (!\Illuminate\Support\Facades\File::exists($dir)) {
                    \Illuminate\Support\Facades\File::makeDirectory($dir, 0775, true);
                }
                foreach ($this->attachments as $file) {
                    $mimeType     = $file->getMimeType();
                    $originalName = $file->getClientOriginalName();
                    $ext          = strtolower($file->getClientOriginalExtension());
                    $filename     = md5(microtime()) . '.' . $ext;
                    copy($file->getRealPath(), $dir . '/' . $filename);
                    PurchaseAttachment::create([
                        'purchase_order_id' => $this->purchase->id,
                        'file_path'         => $filename,
                        'original_name'     => $originalName,
                        'mime_type'         => $mimeType,
                        'file_type'         => PurchaseAttachment::resolveFileType($mimeType ?? ''),
                        'uploaded_by'       => user()->id,
                    ]);
                }
            }
        });
        } catch (\RuntimeException $e) {
            $this->alert('error', $e->getMessage());
            return;
        }

        $this->alert('success', 'Purchase updated successfully!');
        return redirect()->route('purchases.index');
    }

    public function deleteAttachment($attachmentId)
    {
        $attachment = PurchaseAttachment::find($attachmentId);

        if ($attachment && $attachment->purchase_order_id === $this->purchaseId) {
            $attachment->delete(); // Storage file deleted via model booted hook

            // Refresh list
            $this->existingAttachments = array_values(
                array_filter($this->existingAttachments, fn($a) => $a['id'] !== $attachmentId)
            );

            $this->alert('success', 'Attachment deleted.');
        }
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
