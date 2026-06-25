<?php

namespace Modules\Inventory\Livewire\Supplier;

use Livewire\Component;
use Livewire\Attributes\On;
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
use Modules\Inventory\Entities\PurchaseReturn;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\BranchPaymentAccountSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Services\PurchaseOrderService;
use Modules\Inventory\Services\SupplierPaymentGrouper;
use Barryvdh\DomPDF\Facade\Pdf;

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
    public $isSavingPayment = false;
    public $showPaymentViewModal = false;
    public $paymentViewDetails = null;
    public $pendingDeletePaymentId = null;
    public $isDeletingPayment = false;
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
        // 1. Received Purchases → Debits (what we owe the supplier)
        $purchasesQuery = $this->supplier->orders()->where('status', 'received');

        if ($this->locationId) {
            $purchasesQuery->where('location_id', $this->locationId);
        }

        $purchases = $purchasesQuery->get(['id', 'po_number', 'order_date', 'total_amount'])
            ->map(fn ($po) => [
                'date'         => $po->order_date,
                'type'         => 'purchase',
                'description'  => 'Purchase #' . $po->po_number,
                'debit'        => (float) $po->total_amount,
                'credit'       => 0.0,
                'reference_id' => $po->id,
            ]);

        // 2. Supplier Payments → Credits (cash paid to supplier)
        //    With a location filter: include payments linked to that location's POs
        //    AND unallocated/advance payments (purchase_order_id = null) because they
        //    reduce the supplier balance regardless of location.
        $paymentsQuery = $this->supplier->payments()->whereNull('purchase_return_id');

        if ($this->locationId) {
            $paymentsQuery->where(function ($q) {
                $q->whereHas('purchaseOrder', fn ($q2) => $q2->where('location_id', $this->locationId))
                  ->orWhereNull('purchase_order_id');
            });
        }

        $payments = $paymentsQuery->get()
            ->map(fn ($payment) => [
                'date'         => $payment->paid_on,
                'type'         => 'payment',
                'description'  => 'Payment via ' . ucfirst(str_replace('_', ' ', (string) $payment->payment_method)),
                'debit'        => 0.0,
                'credit'       => (float) $payment->amount,
                'reference_id' => $payment->id,
            ]);

        // 3. Purchase Returns → Credits (goods returned, reducing what we owe)
        $returnsQuery = PurchaseReturn::where('supplier_id', $this->supplier->id);

        if ($this->locationId) {
            $returnsQuery->whereHas('purchaseOrder', fn ($q) => $q->where('location_id', $this->locationId));
        }

        $returns = $returnsQuery->get(['id', 'reference_no', 'return_date', 'total_amount'])
            ->map(fn ($ret) => [
                'date'         => $ret->return_date,
                'type'         => 'return',
                'description'  => 'Purchase Return #' . $ret->reference_no,
                'debit'        => 0.0,
                'credit'       => (float) $ret->total_amount,
                'reference_id' => $ret->id,
            ]);

        // 4. Merge all entries and always sort by date asc to compute a correct running balance
        $chronological = $purchases->concat($payments)->concat($returns)->sortBy('date')->values();

        $runningBalance = 0.0;
        $rowOrder = 0;
        $chronological = $chronological->map(function ($entry) use (&$runningBalance, &$rowOrder) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['balance']   = round($runningBalance, 2);
            $entry['row_order'] = $rowOrder++;
            return $entry;
        });

        // 5. Apply date / search filters (after balance is stamped)
        $filtered = $chronological->filter(function ($entry) {
            if ($this->startDate && $this->endDate) {
                $entryDate = is_string($entry['date']) ? $entry['date'] : optional($entry['date'])->toDateString();
                if ($entryDate < $this->startDate || $entryDate > $this->endDate) {
                    return false;
                }
            }

            if ($this->search) {
                $needle = $this->search;
                if (stripos($entry['description'], $needle) === false
                    && stripos((string) $entry['debit'], $needle) === false
                    && stripos((string) $entry['credit'], $needle) === false) {
                    return false;
                }
            }

            return true;
        })->values()->all();

        $this->ledgerEntries = $filtered;

        // 6. Apply display sort (balance column stays as the chronological running balance)
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
                // Use row_order (the chronological computation order) so that DESC is
                // the exact reverse of ASC. Sorting by 'date' alone is unstable for
                // same-date entries and causes the top balance to mismatch the overview.
                $entries = $direction === 'asc'
                    ? $entries->sortBy('row_order')
                    : $entries->sortByDesc('row_order');
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
        $this->isSavingPayment = false;
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
        abort_unless(user_can('Create Purchase Order'), 403);

        if ($this->isSavingPayment) {
            return;
        }

        $lock = Cache::lock(
            'supplier-payment:' . $this->supplier->id . ':' . auth()->id(),
            30,
        );

        if (! $lock->get()) {
            return;
        }

        $this->isSavingPayment = true;

        try {
            $this->validate();

            $paymentAccount = $this->paymentAccount
                ?: BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $this->paymentMethod);

            $path = null;
            if ($this->paymentDocument) {
                $path = $this->paymentDocument->store('supplier-payments', 'public');
            }

            $remaining = (float) $this->paymentAmount;
            $paymentBatchId = (string) Str::uuid();

            // FIFO: oldest received & still-due purchases first
            $duePurchases = $this->supplier->orders()
                ->where('status', 'received')
                ->with('payments')
                ->orderBy('order_date', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->filter(fn ($po) => $po->due_amount > 0);

            $createdPayments = DB::transaction(function () use (&$remaining, $duePurchases, $path, $paymentAccount, $paymentBatchId) {
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
                        'payment_batch_id' => $paymentBatchId,
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
                        'payment_batch_id' => $paymentBatchId,
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

            $this->showPaymentModal = false;
            $this->alert('success', 'Payment recorded successfully');
            $this->loadLedger();
            $this->supplier->refresh();

            $this->dispatch('paymentRecorded');
            $this->dispatch('refreshPurchaseList');
            $this->dispatch('purchaseOrderPaymentSaved');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', 'Failed to record payment: ' . $e->getMessage());
        } finally {
            $this->isSavingPayment = false;
            $lock->release();
        }
    }

    public function viewPayment(int $paymentId): void
    {
        abort_unless(user_can('Show Supplier'), 403);

        $batchPayments = $this->paymentsForBatch($paymentId);

        if ($batchPayments->isEmpty()) {
            return;
        }

        $first = $batchPayments->first();
        $allocations = [];

        foreach ($batchPayments as $payment) {
            if ($payment->purchase_order_id && $payment->purchaseOrder) {
                $purchaseOrder = $payment->purchaseOrder;
                $purchaseTotal = (float) $purchaseOrder->effective_total;
                $appliedAmount = (float) $payment->amount;

                $allocations[] = [
                    'type' => 'purchase',
                    'po_number' => $purchaseOrder->po_number,
                    'invoice_no' => $purchaseOrder->invoice_no,
                    'order_date' => $purchaseOrder->order_date?->format('M d, Y'),
                    'purchase_total' => $purchaseTotal,
                    'applied_amount' => $appliedAmount,
                    'paid_on_purchase' => (float) $purchaseOrder->paid_amount,
                    'due_on_purchase' => (float) $purchaseOrder->due_amount,
                    'payment_status' => $purchaseOrder->payment_status,
                    'is_partial_payment' => $appliedAmount < $purchaseTotal,
                ];
            } else {
                $allocations[] = [
                    'type' => 'unallocated',
                    'applied_amount' => (float) $payment->amount,
                    'note' => $payment->note,
                ];
            }
        }

        $this->paymentViewDetails = [
            'id' => $first->id,
            'paid_on' => $first->paid_on?->format('M d, Y H:i'),
            'payment_method' => ucfirst(str_replace('_', ' ', (string) $first->payment_method)),
            'account_name' => $first->account?->name,
            'amount' => round((float) $batchPayments->sum('amount'), 2),
            'note' => $batchPayments
                ->pluck('note')
                ->filter(fn (?string $value) => $value && ! str_contains($value, 'Advance / unallocated credit'))
                ->unique()
                ->implode(' | ') ?: $first->note,
            'added_by' => $first->addedBy?->name,
            'allocations' => $allocations,
            'payment_ids' => $batchPayments->pluck('id')->all(),
        ];

        $this->showPaymentViewModal = true;
    }

    public function closePaymentViewModal(): void
    {
        $this->showPaymentViewModal = false;
        $this->paymentViewDetails = null;
    }

    public function confirmDeletePayment(int $paymentId): void
    {
        abort_unless(user_can('Delete Purchase Order'), 403);

        $payment = SupplierPayment::query()
            ->where('supplier_id', $this->supplier->id)
            ->find($paymentId);

        if (! $payment) {
            return;
        }

        $batchPayments = $this->paymentsForBatch($paymentId);
        $batchTotal = round((float) $batchPayments->sum('amount'), 2);

        $this->pendingDeletePaymentId = $paymentId;

        $this->alert('warning', __('inventory::modules.supplier.confirmDeletePayment', [
            'amount' => number_format($batchTotal, 2),
        ]), [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => __('app.delete'),
            'cancelButtonText' => __('app.cancel'),
            'onConfirmed' => 'deletePaymentConfirmed',
        ]);
    }

    #[On('deletePaymentConfirmed')]
    public function deletePayment(?int $paymentId = null): void
    {
        abort_unless(user_can('Delete Purchase Order'), 403);

        if ($this->isDeletingPayment) {
            return;
        }

        $paymentId = $paymentId ?? $this->pendingDeletePaymentId;

        $payment = SupplierPayment::query()
            ->where('supplier_id', $this->supplier->id)
            ->find($paymentId);

        if (! $payment) {
            $this->pendingDeletePaymentId = null;

            return;
        }

        $batchPayments = $this->paymentsForBatch($paymentId);

        $this->isDeletingPayment = true;

        try {
            app(PurchaseOrderService::class)->reverseSupplierPaymentBatch($batchPayments);

            $this->supplier->refresh();
            $this->loadLedger();
            $this->resetPage('paymentsPage');

            $this->dispatch('paymentRecorded');
            $this->dispatch('refreshPurchaseList');
            $this->dispatch('purchaseOrderPaymentSaved');

            $this->alert('success', __('inventory::modules.supplier.paymentDeleted'));
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        } finally {
            $this->isDeletingPayment = false;
            $this->pendingDeletePaymentId = null;
        }
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

    public function downloadPdf(int $purchaseOrderId)
    {
        abort_if(!user_can('Show Purchase Order'), 403);

        $purchaseOrder = PurchaseOrder::query()
            ->where('id', $purchaseOrderId)
            ->where('supplier_id', $this->supplier->id)
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->firstOrFail();

        $purchaseOrder->load([
            'supplier',
            'location.branch',
            'items.inventoryItem.unit',
            'items.inventoryItem.category',
            'creator',
            'payments.account',
            'attachments',
        ]);

        $pdf = Pdf::loadView('inventory::pdfs.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
        ])->setPaper('a4');

        $pdf->getDomPDF()->set_option('defaultFont', 'Arial');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "PO-{$purchaseOrder->po_number}.pdf");
    }

    public function confirmDeletePurchase($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
        if ($purchaseOrder && !in_array($purchaseOrder->status, ['cancelled'], true)) {
            $this->purchaseOrderToDelete = $purchaseOrder;
            $this->confirmingDeletion = true;
        }
    }

    public function deletePurchase(PurchaseOrderService $purchaseOrderService)
    {
        abort_if(!user_can('Delete Purchase Order'), 403);

        if ($this->purchaseOrderToDelete) {
            try {
                $purchaseOrderService->deletePurchaseOrder($this->purchaseOrderToDelete);
                $this->alert('success', trans('inventory::modules.purchaseOrder.deleted_successfully'));
                $this->supplier->refresh();
                if ($this->activeTab === 'ledger') {
                    $this->loadLedger();
                }
            } catch (\Throwable $e) {
                $this->alert('error', $e->getMessage());
            }
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
            'paymentAccounts' => PaymentAccount::where('branch_id', branch()->id)->get(),
            'locations' => PurchaseLocation::getForRestaurant(restaurant()->id),
            'statuses' => [
                'ordered'             => trans('inventory::modules.purchaseOrder.status.ordered'),
                'pending'             => trans('inventory::modules.purchaseOrder.status.pending'),
                'sent'                => trans('inventory::modules.purchaseOrder.status.sent'),
                'partially_received'  => trans('inventory::modules.purchaseOrder.status.partially_received'),
                'received'            => trans('inventory::modules.purchaseOrder.status.received'),
                'cancelled'           => trans('inventory::modules.purchaseOrder.status.cancelled'),
            ],
            'purchases' => $this->purchases,
            'paymentGroups' => $this->paymentGroups,
        ]);
    }

    protected function paymentsForBatch(int $paymentId)
    {
        $allPayments = $this->supplier->payments()
            ->with(['purchaseOrder', 'account', 'addedBy'])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get();

        return SupplierPaymentGrouper::batchPaymentsFor($allPayments, $paymentId);
    }

    public function getPaymentGroupsProperty()
    {
        $payments = $this->supplier->payments()
            ->with(['account', 'purchaseOrder'])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('paid_on', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get();

        $groups = SupplierPaymentGrouper::group($payments);

        if ($this->search) {
            $needle = mb_strtolower($this->search);
            $groups = $groups->filter(function (array $group) use ($needle) {
                return str_contains(mb_strtolower((string) $group['payment_method']), $needle)
                    || str_contains(mb_strtolower((string) ($group['note'] ?? '')), $needle)
                    || str_contains((string) $group['amount'], $needle)
                    || str_contains(mb_strtolower((string) ($group['account']?->name ?? '')), $needle);
            })->values();
        }

        if ($this->locationId) {
            $groups = $groups->filter(function (array $group) {
                return $group['payments']->contains(function (SupplierPayment $payment) {
                    return $payment->purchaseOrder
                        && (int) $payment->purchaseOrder->location_id === (int) $this->locationId;
                });
            })->values();
        }

        $page = max(1, (int) $this->getPage('paymentsPage'));
        $total = $groups->count();
        $items = $groups->slice(($page - 1) * $this->perPage, $this->perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $page,
            ['pageName' => 'paymentsPage'],
        );
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
}
