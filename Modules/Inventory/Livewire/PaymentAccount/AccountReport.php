<?php

namespace Modules\Inventory\Livewire\PaymentAccount;

use Livewire\Component;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\Payment;
use App\Models\Expenses;
use Livewire\WithPagination;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountReport extends Component
{
    use WithPagination, LivewireAlert;

    public $accountId;
    public $startDate;
    public $endDate;
    public $search = '';
    public $type = 'all'; // all, credit, debit, unlinked
    
    // Link Payment Modal
    public $showLinkModal = false;
    public $linkAccountId;
    public $linkPaymentType; // 'expense' or 'payment'
    public $linkPaymentId;
    public $linkAmount;
    public $linkDescription;

    protected $queryString = ['accountId', 'startDate', 'endDate', 'type', 'search'];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        if(request()->get('accountId') === 'unlinked') {
            $this->type = 'unlinked';
            $this->accountId = 'unlinked';
        }
    }

    public function updated($field)
    {
        if (in_array($field, ['accountId', 'startDate', 'endDate', 'type', 'search'])) {
            $this->resetPage();
        }
        
        if ($field === 'accountId') {
            if ($this->accountId === 'unlinked') {
                $this->type = 'unlinked';
            } elseif ($this->type === 'unlinked') {
                // Reset type if leaving unlinked account view
                $this->type = 'all';
            }
        }
    }

    public function render()
    {
        // 1. Fetch Unlinked Items
        // We always fetch unlinked items if type is 'all' or 'unlinked'
        // or if we are in "All Accounts" view
        
        $shouldFetchUnlinked = ($this->type === 'all' || $this->type === 'unlinked') && (!$this->accountId || $this->accountId === 'unlinked');

        $unlinkedCollection = collect();

        if ($shouldFetchUnlinked) {
            $expenses = Expenses::whereNull('payment_account_id')
                ->where('payment_status', 'paid')
                ->when($this->search, function($q) {
                    $q->where('expense_title', 'like', '%' . $this->search . '%');
                })
                // Date filter for unlinked items (using created_at)
                ->when($this->startDate, function($q) {
                    $q->whereDate('created_at', '>=', $this->startDate);
                })
                ->when($this->endDate, function($q) {
                    $q->whereDate('created_at', '<=', $this->endDate);
                })
                ->get()
                ->map(function($item) {
                    return $this->formatUnlinkedItem($item, 'expense');
                });
            
            $payments = Payment::whereNull('payment_account_id')
                ->when($this->search, function($q) {
                    $q->whereHas('order', function($q) {
                        $q->where('order_number', 'like', '%' . $this->search . '%');
                    });
                })
                // Date filter
                ->when($this->startDate, function($q) {
                    $q->whereDate('created_at', '>=', $this->startDate);
                })
                ->when($this->endDate, function($q) {
                    $q->whereDate('created_at', '<=', $this->endDate);
                })
                ->get()
                ->map(function($item) {
                    return $this->formatUnlinkedItem($item, 'payment');
                });
            
            $unlinkedCollection = $expenses->concat($payments);
        }

        // 2. Fetch Linked Transactions
        // We fetch linked transactions unless we are strictly in 'unlinked' view
        
        $linkedCollection = collect();

        if ($this->type !== 'unlinked') {
            $query = AccountTransaction::query()
                ->with('account', 'reference');

            if ($this->accountId && $this->accountId !== 'unlinked') {
                $query->where('payment_account_id', $this->accountId);
            }

            if ($this->startDate) {
                $query->whereDate('transaction_date', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('transaction_date', '<=', $this->endDate);
            }

            if ($this->type !== 'all') {
                $query->where('type', $this->type);
            }

            if ($this->search) {
                $query->where('description', 'like', '%' . $this->search . '%');
            }

            $linkedCollection = $query->get()->map(function($item) {
                return $this->formatLinkedItem($item);
            });
        }

        // 3. Merge and Sort
        $mergedCollection = $linkedCollection->concat($unlinkedCollection)->sortByDesc('transaction_date');
        $totalItems = $mergedCollection->count();

        // 4. Paginate
        $perPage = 20;
        $page = $this->getPage();
        
        $transactions = new LengthAwarePaginator(
            $mergedCollection->forPage($page, $perPage)->values(), 
            $totalItems, 
            $perPage, 
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('inventory::livewire.payment-account.account-report', [
            'transactions' => $transactions,
            'accounts' => PaymentAccount::all(),
            'totalCredit' => $this->calculateTotal('credit'),
            'totalDebit' => $this->calculateTotal('debit'),
        ]);
    }

    private function formatUnlinkedItem($item, $type)
    {
        $obj = new \stdClass();
        $obj->id = 'unlinked_' . $item->id; // Unique ID for keying
        $obj->original_id = $item->id;
        $obj->transaction_date = \Carbon\Carbon::parse($item->created_at ?? now());
        $obj->account_name = 'Unlinked';
        $obj->account_id = null;
        
        if ($type === 'expense') {
            $obj->description = $item->expense_title ?? 'Expense';
            $obj->type = 'credit';
            $obj->reference_type = 'App\Models\Expenses';
            $obj->link_type = 'expense';
        } else {
            $obj->description = 'Order Payment #' . ($item->order->order_number ?? $item->order_id);
            $obj->type = 'debit';
            $obj->reference_type = 'App\Models\Payment';
            $obj->link_type = 'payment';
        }

        $obj->amount = $item->amount;
        $obj->is_linked = false;
        
        // Fields for linking
        $obj->link_id = $item->id;
        
        return $obj;
    }

    private function formatLinkedItem($item)
    {
        $obj = new \stdClass();
        $obj->id = 'linked_' . $item->id;
        $obj->original_id = $item->id;
        $obj->transaction_date = $item->transaction_date;
        $obj->account_name = $item->account->name ?? 'Unknown Account';
        $obj->account_id = $item->payment_account_id;
        $obj->description = $item->description;
        $obj->type = $item->type;
        $obj->amount = $item->amount;
        $obj->is_linked = true;
        
        // Determine original source for re-linking
        // We can only re-link if it came from Expense or Payment
        // If it's a Transfer or Deposit, we might not want to re-link easily here
        
        $obj->can_relink = false;
        $obj->link_type = null;
        $obj->link_id = null;
        $obj->reference_type = $item->reference_type;
        $obj->reference_id = $item->reference_id;

        if ($item->reference_type === 'App\Models\Expenses' || $item->reference_type === 'Modules\Inventory\Entities\SupplierPayment') {
            // Note: Current logic only supports Expense and Order Payment in re-linking
            // If we want to support SupplierPayment re-linking, we need to adapt saveLink
            if ($item->reference_type === 'App\Models\Expenses') {
                $obj->can_relink = true;
                $obj->link_type = 'expense';
                $obj->link_id = $item->reference_id;
            }
        } elseif ($item->reference_type === 'App\Models\Payment') {
            $obj->can_relink = true;
            $obj->link_type = 'payment';
            $obj->link_id = $item->reference_id;
        }

        return $obj;
    }

    public function calculateTotal($type)
    {
        // Calculate totals based on CURRENT FILTERS
        // This should match the visual data
        // Ideally we iterate over the $mergedCollection but that's expensive to rebuild just for totals
        // So we will query DB.
        
        $total = 0;

        // 1. Linked
        if ($this->type !== 'unlinked') {
            $query = AccountTransaction::query();
            if ($this->accountId && $this->accountId !== 'unlinked') {
                $query->where('payment_account_id', $this->accountId);
            }
            if ($this->startDate) {
                $query->whereDate('transaction_date', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('transaction_date', '<=', $this->endDate);
            }
            if ($this->type !== 'all') {
                $query->where('type', $this->type);
            }
            if ($this->search) {
                $query->where('description', 'like', '%' . $this->search . '%');
            }
            $total += $query->where('type', $type)->sum('amount');
        }

        // 2. Unlinked
        $shouldFetchUnlinked = ($this->type === 'all' || $this->type === 'unlinked') && (!$this->accountId || $this->accountId === 'unlinked');
        
        if ($shouldFetchUnlinked) {
            // Expenses (Credit)
            if ($type === 'credit' || $type === 'all' || $type === 'unlinked') { // Unlinked type usually implies showing both?
                 // Wait, if type is 'credit', we include unlinked expenses.
                 // If type is 'debit', we include unlinked payments.
                 if ($type === 'credit') {
                    $query = Expenses::whereNull('payment_account_id')->where('payment_status', 'paid');
                    if ($this->startDate) $query->whereDate('created_at', '>=', $this->startDate);
                    if ($this->endDate) $query->whereDate('created_at', '<=', $this->endDate);
                    if ($this->search) $query->where('expense_title', 'like', '%' . $this->search . '%');
                    $total += $query->sum('amount');
                 }
            }
            
            // Payments (Debit)
            if ($type === 'debit') {
                 $query = Payment::whereNull('payment_account_id');
                 if ($this->startDate) $query->whereDate('created_at', '>=', $this->startDate);
                 if ($this->endDate) $query->whereDate('created_at', '<=', $this->endDate);
                 if ($this->search) {
                     $query->whereHas('order', function($q) {
                        $q->where('order_number', 'like', '%' . $this->search . '%');
                     });
                 }
                 $total += $query->sum('amount');
            }
        }

        return $total;
    }

    public function export()
    {
        return redirect()->route('payment-accounts.export', [
            'accountId' => $this->accountId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'type' => $this->type,
            'search' => $this->search,
        ]);
    }

    public function openLinkModal($type, $id)
    {
        $this->linkPaymentType = $type;
        $this->linkPaymentId = $id;
        
        if ($type === 'expense') {
            $item = Expenses::find($id);
            $this->linkAmount = $item->amount;
            $this->linkDescription = $item->expense_title;
            // Pre-select account if already linked
            $this->linkAccountId = $item->payment_account_id;
        } else {
            $item = Payment::find($id);
            $this->linkAmount = $item->amount;
            $this->linkDescription = 'Order Payment #' . ($item->order->order_number ?? $item->order_id);
            // Pre-select account if already linked
            $this->linkAccountId = $item->payment_account_id;
        }

        $this->showLinkModal = true;
    }

    public function saveLink()
    {
        $this->validate([
            'linkAccountId' => 'required|exists:payment_accounts,id',
        ]);

        DB::transaction(function () {
            $newAccount = PaymentAccount::find($this->linkAccountId);
            
            if ($this->linkPaymentType === 'expense') {
                $item = Expenses::find($this->linkPaymentId);
                $oldAccountId = $item->payment_account_id;
                
                // If account changed
                if ($oldAccountId != $newAccount->id) {
                    // Revert old account balance if it was linked
                    if ($oldAccountId) {
                        $oldAccount = PaymentAccount::find($oldAccountId);
                        if ($oldAccount) {
                            $oldAccount->increment('current_balance', $item->amount); // Revert expense (add back money)
                            // Delete old transaction
                            AccountTransaction::where('payment_account_id', $oldAccountId)
                                ->where('reference_type', 'App\Models\Expenses')
                                ->where('reference_id', $item->id)
                                ->delete();
                        }
                    }
                    
                    // Update item
                    $item->payment_account_id = $newAccount->id;
                    $item->save();

                    // Update new account
                    $newAccount->decrement('current_balance', $item->amount);
                    
                    // Create new transaction
                    AccountTransaction::create([
                        'payment_account_id' => $newAccount->id,
                        'amount' => $item->amount,
                        'type' => 'credit',
                        'reference_type' => get_class($item),
                        'reference_id' => $item->id,
                        'description' => 'Expense: ' . $item->expense_title,
                        'transaction_date' => now(), // Or keep original date? Better to keep original date if possible, but created_at is safe.
                    ]);
                }
            } else {
                // Payment
                $item = Payment::find($this->linkPaymentId);
                $oldAccountId = $item->payment_account_id;

                if ($oldAccountId != $newAccount->id) {
                    // Revert old account
                    if ($oldAccountId) {
                        $oldAccount = PaymentAccount::find($oldAccountId);
                        if ($oldAccount) {
                            $oldAccount->decrement('current_balance', $item->amount); // Revert payment (remove money)
                             // Delete old transaction
                            AccountTransaction::where('payment_account_id', $oldAccountId)
                                ->where('reference_type', 'App\Models\Payment')
                                ->where('reference_id', $item->id)
                                ->delete();
                        }
                    }

                    // Update item
                    $item->payment_account_id = $newAccount->id;
                    $item->save();

                    // Update new account
                    $newAccount->increment('current_balance', $item->amount);
                    
                    // Create new transaction
                    AccountTransaction::create([
                        'payment_account_id' => $newAccount->id,
                        'amount' => $item->amount,
                        'type' => 'debit',
                        'reference_type' => get_class($item),
                        'reference_id' => $item->id,
                        'description' => 'Order Payment #' . ($item->order->order_number ?? $item->order_id),
                        'transaction_date' => now(),
                    ]);
                }
            }
        });

        $this->showLinkModal = false;
        $this->alert('success', 'Payment account updated successfully');
    }
}
