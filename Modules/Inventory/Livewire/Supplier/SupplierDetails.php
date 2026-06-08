<?php

namespace Modules\Inventory\Livewire\Supplier;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\PurchaseOrderExport;
use Modules\Inventory\Exports\SupplierPaymentExport;
use Modules\Inventory\Exports\SupplierLedgerExport;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\BranchPaymentAccountSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class SupplierDetails extends Component
{
    use WithFileUploads, LivewireAlert, WithPagination;

    public $supplier;
    public $activeTab = 'overview'; // overview, ledger, purchases, stock, documents, settings
    
    // Filters
    public $locationId;
    public $search = '';
    public $startDate = null;
    public $endDate = null;
    public $perPage = 10;

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

    public string $ledgerSortField = 'date';
    public string $ledgerSortDirection = 'desc';

    // Stock Data
    public $stockItems = [];

    // Purchase Actions
    public $confirmingDeletion = false;
    public $purchaseOrderToDelete;

    protected $listeners = [
        'paymentRecorded' => 'onSupplierPaymentRecorded',
        'refreshPurchaseList' => 'onSupplierPaymentRecorded',
        'purchaseOrderPaymentSaved' => 'onSupplierPaymentRecorded',
    ];

    public function onSupplierPaymentRecorded()
    {
        $this->supplier->refresh();
        if ($this->activeTab === 'ledger') {
            $this->loadLedger();
        }
    }

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

        // Location filter defaults to All
        $this->locationId = null;

        $this->loadLedger();
        $this->loadStock();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->reset(['search', 'startDate', 'endDate', 'perPage']);
        $this->perPage = 10;
        $this->resetPage();

        if ($tab === 'ledger') {
            $this->loadLedger();
        }
        if ($tab === 'stock') {
            $this->loadStock();
        }
    }

    public function updatedLocationId()
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
        // 1. Get all received Purchases (Debits)
        $purchasesQuery = $this->supplier->orders()
            ->where('status', 'received'); // Only count received goods as debt
        
        if ($this->locationId) {
            $purchasesQuery->where('location_id', $this->locationId);
        }

        $purchases = $purchasesQuery->with('items')->get()
            ->map(function ($po) {
                return [
                    'date' => $po->order_date,
                    'type' => 'purchase',
                    'description' => 'Purchase #' . $po->po_number,
                    'debit' => (float) $po->final_total,
                    'credit' => 0,
                    'reference_id' => $po->id
                ];
            });
        
        // 2. Get all Payments (Credits)
        $paymentsQuery = $this->supplier->payments();

        if ($this->locationId) {
            // When location filter is active, only include payments tied to purchases in that location
            $paymentsQuery->whereHas('purchaseOrder', function ($q) {
                $q->where('location_id', $this->locationId);
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
        $allEntries = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $runningBalance;
            return $entry;
        });

        // 5. Apply Filters
        $this->ledgerEntries = $allEntries->filter(function ($entry) {
            // Date Filter
            if ($this->startDate && $this->endDate) {
                 if ($entry['date'] < $this->startDate . ' 00:00:00' || $entry['date'] > $this->endDate . ' 23:59:59') {
                     return false;
                 }
            }
            
            // Search Filter
            if ($this->search) {
                if (stripos($entry['description'], $this->search) === false && 
                    stripos((string)$entry['debit'], $this->search) === false && 
                    stripos((string)$entry['credit'], $this->search) === false) {
                    return false;
                }
            }
            
            return true;
        })->values()->all();

        $this->applyLedgerSorting();
    }

    public function sortLedgerBy(string $field): void
    {
        $allowed = ['date', 'description', 'debit', 'credit', 'balance'];
        if (!in_array($field, $allowed, true)) {
            return;
        }

        if ($this->ledgerSortField === $field) {
            $this->ledgerSortDirection = $this->ledgerSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ledgerSortField = $field;
            $this->ledgerSortDirection = $field === 'date' ? 'desc' : 'asc';
        }

        $this->applyLedgerSorting();
    }

    protected function applyLedgerSorting(): void
    {
        $entries = collect($this->ledgerEntries);
        $direction = $this->ledgerSortDirection === 'asc' ? 'asc' : 'desc';

        switch ($this->ledgerSortField) {
            case 'description':
                $entries = $direction === 'asc'
                    ? $entries->sortBy('description', SORT_NATURAL | SORT_FLAG_CASE)
                    : $entries->sortByDesc('description', SORT_NATURAL | SORT_FLAG_CASE);
                break;

            case 'debit':
                $entries = $direction === 'asc'
                    ? $entries->sortBy(fn ($e) => (float) ($e['debit'] ?? 0))
                    : $entries->sortByDesc(fn ($e) => (float) ($e['debit'] ?? 0));
                break;

            case 'credit':
                $entries = $direction === 'asc'
                    ? $entries->sortBy(fn ($e) => (float) ($e['credit'] ?? 0))
                    : $entries->sortByDesc(fn ($e) => (float) ($e['credit'] ?? 0));
                break;

            case 'balance':
                $entries = $direction === 'asc'
                    ? $entries->sortBy(fn ($e) => (float) ($e['balance'] ?? 0))
                    : $entries->sortByDesc(fn ($e) => (float) ($e['balance'] ?? 0));
                break;

            case 'date':
            default:
                $entries = $direction === 'asc'
                    ? $entries->sortBy('date')
                    : $entries->sortByDesc('date');
                break;
        }

        $this->ledgerEntries = $entries->values()->all();
    }

    public function loadStock()
    {
        // Fetch items purchased from this supplier
        // We'll look at received purchases
        $query = $this->supplier->orders()
            ->where('status', 'received')
            ->with(['items.inventoryItem.unit']);

        if ($this->locationId) {
            $query->where('location_id', $this->locationId);
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
                $items[$itemId]['total_qty'] += (float) ($poItem->quantity ?? 0);
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
        $this->supplier->refresh();
        $this->paymentAmount = number_format(max(0, (float) $this->supplier->balance), 2, '.', '');
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethod = 'cash';
        $this->paymentAccount = BranchPaymentAccountSetting::resolveDefaultAccountId(
            branch()->id,
            $this->paymentMethod
        );
        $this->paymentNote = '';
        $this->paymentDocument = null;
        $this->showPaymentModal = true;
    }

    public function updatedPaymentMethod($value)
    {
        if ($value) {
            $this->paymentAccount = BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $value);
        }
    }

    public function savePayment()
    {
        $this->validate();

        $paymentAccount = $this->paymentAccount
            ?: BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $this->paymentMethod);

        $path = null;
        if ($this->paymentDocument) {
            $path = $this->paymentDocument->store('supplier-payments', 'public');
        }

        $remaining = (float) $this->paymentAmount;

        // FIFO: oldest received & still-due purchases first
        $duePurchases = $this->supplier->orders()
            ->where('status', 'received')
            ->with('payments')
            ->orderBy('order_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->filter(fn ($po) => $po->due_amount > 0);

        try {
            $createdPayments = DB::transaction(function () use (&$remaining, $duePurchases, $path, $paymentAccount) {
                $payments = [];

                foreach ($duePurchases as $po) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $allocate = min($remaining, (float) $po->due_amount);
                    if ($allocate <= 0) {
                        continue;
                    }

                    $payments[] = SupplierPayment::create([
                        'supplier_id' => $this->supplier->id,
                        'purchase_order_id' => $po->id,
                        'payment_account_id' => $paymentAccount,
                        'amount' => $allocate,
                        'paid_on' => $this->paymentDate,
                        'payment_method' => $this->paymentMethod,
                        'note' => $this->paymentNote,
                        'document_path' => $path,
                        'added_by' => Auth::id(),
                    ]);

                    $remaining = round($remaining - $allocate, 2);
                }

                // If anything is left over (overpayment or no due PO), store as advance/credit
                if ($remaining > 0) {
                    $payments[] = SupplierPayment::create([
                        'supplier_id' => $this->supplier->id,
                        'purchase_order_id' => null,
                        'payment_account_id' => $paymentAccount,
                        'amount' => $remaining,
                        'paid_on' => $this->paymentDate,
                        'payment_method' => $this->paymentMethod,
                        'note' => trim(($this->paymentNote ? $this->paymentNote . ' | ' : '') . 'Advance / unallocated credit'),
                        'document_path' => $path,
                        'added_by' => Auth::id(),
                    ]);
                    $remaining = 0;
                }

                return $payments;
            });
        } catch (\Throwable $e) {
            $this->alert('error', 'Failed to record payment: ' . $e->getMessage());
            return;
        }

        // Update Payment Account Balance once for the full amount and log a single transaction
        if ($paymentAccount) {
            $account = PaymentAccount::find($paymentAccount);
            if ($account) {
                $account->decrement('current_balance', $this->paymentAmount);

                AccountTransaction::create([
                    'payment_account_id' => $account->id,
                    'amount' => $this->paymentAmount,
                    'type' => 'credit', // Money Out
                    'reference_type' => SupplierPayment::class,
                    'reference_id' => optional($createdPayments[0] ?? null)->id,
                    'description' => 'Payment to Supplier: ' . $this->supplier->name . ($this->paymentNote ? ' - ' . $this->paymentNote : ''),
                    'transaction_date' => $this->paymentDate,
                ]);
            }
        }

        $this->alert('success', 'Payment recorded successfully');
        $this->showPaymentModal = false;
        $this->loadLedger();
        $this->supplier->refresh(); // Update overview stats

        // Notify the purchases list (and any other listening components) to refresh
        $this->dispatch('paymentRecorded');
        $this->dispatch('refreshPurchaseList');
        $this->dispatch('purchaseOrderPaymentSaved');
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

    public function confirmDeletePurchase($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
        if ($purchaseOrder && !in_array($purchaseOrder->status, ['received', 'cancelled'], true)) {
            $this->purchaseOrderToDelete = $purchaseOrder;
            $this->confirmingDeletion = true;
        }
    }

    public function deletePurchase()
    {
        if ($this->purchaseOrderToDelete) {
            $this->purchaseOrderToDelete->delete();
            $this->alert('success', trans('inventory::modules.purchaseOrder.deleted_successfully'));
            $this->supplier->refresh();
        }
        $this->confirmingDeletion = false;
        $this->purchaseOrderToDelete = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
        if ($this->activeTab === 'ledger') $this->loadLedger();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
        if ($this->activeTab === 'ledger') $this->loadLedger();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
        if ($this->activeTab === 'ledger') $this->loadLedger();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'startDate', 'endDate']);
        $this->resetPage();
        if ($this->activeTab === 'ledger') $this->loadLedger();
    }
    
    public function export()
    {
        switch ($this->activeTab) {
            case 'purchases':
                return Excel::download(new PurchaseOrderExport($this->search, $this->startDate, $this->endDate, $this->supplier->id), 'supplier-purchases.xlsx');
            case 'payments':
                return Excel::download(new SupplierPaymentExport($this->supplier->id, $this->search, $this->startDate, $this->endDate), 'supplier-payments.xlsx');
            case 'ledger':
                return Excel::download(new SupplierLedgerExport($this->ledgerEntries), 'supplier-ledger.xlsx');
        }
    }

    public function render()
    {
        return view('inventory::livewire.supplier.supplier-details', [
            'paymentAccounts' => PaymentAccount::query()->get(),
            'locations' => PurchaseLocation::getForRestaurant(restaurant()->id),
            'statuses' => [
                'ordered' => trans('inventory::modules.purchaseOrder.status.ordered'),
                'pending' => trans('inventory::modules.purchaseOrder.status.pending'),
                'received' => trans('inventory::modules.purchaseOrder.status.received'),
                'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
            ],
            'purchases' => $this->purchases,
            'payments' => $this->payments,
        ]);
    }

    public function getPurchasesProperty()
    {
        return $this->supplier->orders()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('id', 'like', '%' . $this->search . '%')
                        ->orWhere('po_number', 'like', '%' . $this->search . '%')
                        ->orWhere('invoice_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->locationId, function ($query) {
                $query->where('location_id', $this->locationId);
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('order_date', [$this->startDate, $this->endDate]);
            })
            ->latest('order_date')
            ->paginate($this->perPage, ['*'], 'purchasesPage');
    }

    public function getPaymentsProperty()
    {
        return $this->supplier->payments()
            ->with('account')
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('payment_method', 'like', '%' . $this->search . '%')
                      ->orWhere('note', 'like', '%' . $this->search . '%')
                      ->orWhereHas('account', function($sq) {
                          $sq->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->locationId, function ($query) {
                $query->whereHas('purchaseOrder', function ($q) {
                    $q->where('location_id', $this->locationId);
                });
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('paid_on', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->latest('paid_on')
            ->paginate($this->perPage, ['*'], 'paymentsPage');
    }
}
