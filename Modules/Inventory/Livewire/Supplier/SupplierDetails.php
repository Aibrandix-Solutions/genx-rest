<?php

namespace Modules\Inventory\Livewire\Supplier;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\PaymentAccount;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class SupplierDetails extends Component
{
    use WithFileUploads, LivewireAlert;

    public $supplier;
    public $activeTab = 'overview'; // overview, ledger, purchases, stock, documents, settings
    
    // Filters
    public $branchId;

    // Payment Modal Properties
    public $showPaymentModal = false;
    public $paymentAmount;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentAccount;
    public $paymentNote;
    public $paymentDocument;

    // Document Upload Properties
    public $newDocument;
    public $newDocumentName;

    // Ledger Data
    public $ledgerEntries = [];

    // Stock Data
    public $stockItems = [];

    protected $rules = [
        'paymentAmount' => 'required|numeric|min:0.01',
        'paymentDate' => 'required|date',
        'paymentMethod' => 'required|string',
        'paymentAccount' => 'nullable|exists:payment_accounts,id',
        'paymentDocument' => 'nullable|file|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png|max:10240', // 10MB max
        'paymentNote' => 'nullable|string|max:500',
    ];

    public function mount($supplier)
    {
        $this->supplier = $supplier;
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        
        // Default to current branch if available in context, otherwise null (All)
        if (function_exists('branch') && branch()) {
            $this->branchId = branch()->id;
        }

        $this->loadLedger();
        $this->loadStock();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'ledger') {
            $this->loadLedger();
        }
        if ($tab === 'stock') {
            $this->loadStock();
        }
    }

    public function updatedBranchId()
    {
        if ($this->activeTab === 'ledger') {
            $this->loadLedger();
        }
        if ($this->activeTab === 'stock') {
            $this->loadStock();
        }
        // Overview tab stats might need updating too if we want them filtered by branch
        // But for now user specifically asked for Ledger and Stock
    }

    public function loadLedger()
    {
        // 1. Get all confirmed Purchase Orders (Debits)
        $purchasesQuery = $this->supplier->orders()
            ->whereIn('status', ['received', 'partially_received']); // Only count received goods as debt
        
        if ($this->branchId) {
            $purchasesQuery->where('branch_id', $this->branchId);
        }

        $purchases = $purchasesQuery->get()
            ->map(function ($po) {
                return [
                    'date' => $po->order_date,
                    'type' => 'purchase',
                    'description' => 'Purchase Order #' . $po->po_number,
                    'debit' => $po->total_amount,
                    'credit' => 0,
                    'reference_id' => $po->id
                ];
            });
        
        // 2. Get all Payments (Credits)
        $paymentsQuery = $this->supplier->payments();

        if ($this->branchId) {
            $paymentsQuery->whereHas('account', function($q) {
                $q->where('branch_id', $this->branchId);
            });
        }

        $payments = $paymentsQuery->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->paid_on,
                    'type' => 'payment',
                    'description' => 'Payment via ' . ucfirst($payment->payment_method),
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'reference_id' => $payment->id
                ];
            });

        // 3. Merge and Sort
        $entries = $purchases->concat($payments)->sortBy('date');

        // 4. Calculate Running Balance
        $runningBalance = 0;
        $this->ledgerEntries = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $runningBalance;
            return $entry;
        });
    }

    public function loadStock()
    {
        // Fetch items purchased from this supplier
        // We'll look at received purchase orders
        $query = $this->supplier->orders()
            ->whereIn('status', ['received', 'partially_received'])
            ->with([
                'items.inventoryItem' => function($q) {
                    $q->withoutGlobalScopes();
                },
                'items.inventoryItem.unit' => function($q) {
                    $q->withoutGlobalScopes();
                }
            ]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $orders = $query->get();
        
        // Aggregate items
        $items = [];
        foreach ($orders as $order) {
            foreach ($order->items as $poItem) {
                $itemId = $poItem->inventory_item_id;
                if (!isset($items[$itemId])) {
                    $items[$itemId] = [
                        'name' => $poItem->inventoryItem->name ?? 'Deleted Item',
                        'unit' => $poItem->inventoryItem?->unit?->symbol ?? '-',
                        'total_qty' => 0,
                        'last_cost' => 0,
                        'last_purchased' => null
                    ];
                }
                
                // Update aggregates
                $items[$itemId]['total_qty'] += $poItem->received_quantity;
                // Use the most recent price/date
                if (is_null($items[$itemId]['last_purchased']) || $order->order_date > $items[$itemId]['last_purchased']) {
                    $items[$itemId]['last_cost'] = $poItem->unit_price;
                    $items[$itemId]['last_purchased'] = $order->order_date;
                }
            }
        }

        $this->stockItems = collect($items)->values();
    }

    public function openPaymentModal()
    {
        $this->resetValidation();
        $this->paymentAmount = '';
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethod = 'cash';
        $this->paymentAccount = null;
        $this->paymentNote = '';
        $this->paymentDocument = null;
        $this->showPaymentModal = true;
    }

    public function savePayment()
    {
        $this->validate();

        $path = null;
        if ($this->paymentDocument) {
            $path = $this->paymentDocument->store('supplier-payments', 'public');
        }

        $payment = SupplierPayment::create([
            'supplier_id' => $this->supplier->id,
            'payment_account_id' => $this->paymentAccount,
            'amount' => $this->paymentAmount,
            'paid_on' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'note' => $this->paymentNote,
            'document_path' => $path,
            'added_by' => Auth::id(),
        ]);

        // Update Payment Account Balance if selected and log transaction
        if ($this->paymentAccount) {
            $account = PaymentAccount::find($this->paymentAccount);
            if ($account) {
                $account->decrement('current_balance', $this->paymentAmount);

                // Log Transaction
                \Modules\Inventory\Entities\AccountTransaction::create([
                    'payment_account_id' => $account->id,
                    'amount' => $this->paymentAmount,
                    'type' => 'credit', // Money Out
                    'reference_type' => get_class($payment),
                    'reference_id' => $payment->id,
                    'description' => 'Payment to Supplier: ' . $this->supplier->name . ($this->paymentNote ? ' - ' . $this->paymentNote : ''),
                    'transaction_date' => $this->paymentDate,
                ]);
            }
        }

        $this->alert('success', 'Payment recorded successfully');
        $this->showPaymentModal = false;
        $this->loadLedger();
        $this->supplier->refresh(); // Update overview stats
    }

    public function uploadDocument()
    {
        $this->validate([
            'newDocumentName' => 'required|string|max:255',
            'newDocument' => 'required|file|max:10240',
        ]);

        $path = $this->newDocument->store('supplier-documents', 'public');

        $this->supplier->documents()->create([
            'name' => $this->newDocumentName,
            'file_path' => $path,
            'file_type' => $this->newDocument->extension(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->alert('success', 'Document uploaded successfully');
        $this->newDocument = null;
        $this->newDocumentName = '';
    }

    public function toggleStatus()
    {
        $this->supplier->is_active = !$this->supplier->is_active;
        $this->supplier->save();
        $this->alert('success', 'Supplier status updated');
    }

    public function render()
    {
        $accountQuery = PaymentAccount::query();
        
        if ($this->branchId) {
             $accountQuery->where('branch_id', $this->branchId);
        }

        return view('inventory::livewire.supplier.supplier-details', [
            'paymentAccounts' => $accountQuery->get(),
            'branches' => Branch::all(),
            'statuses' => [
                'draft' => trans('inventory::modules.purchaseOrder.status.draft'),
                'sent' => trans('inventory::modules.purchaseOrder.status.sent'),
                'received' => trans('inventory::modules.purchaseOrder.status.received'),
                'partially_received' => trans('inventory::modules.purchaseOrder.status.partially_received'),
                'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
            ],
        ]);
    }
}
