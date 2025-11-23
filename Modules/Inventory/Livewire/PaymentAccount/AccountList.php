<?php

namespace Modules\Inventory\Livewire\PaymentAccount;

use Livewire\Component;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransfer;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\Payment; // App Payments
use App\Models\Expenses; // App Expenses
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountList extends Component
{
    use WithPagination, LivewireAlert;

    public $search = '';
    
    // Account Modal
    public $showModal = false;
    public $accountId;
    public $name;
    public $account_number;
    public $description;
    public $type = 'cash';
    public $current_balance = 0;

    // Transfer Modal
    public $showTransferModal = false;
    public $transferFromId;
    public $transferToId;
    public $transferAmount;
    public $transferDate;
    public $transferDescription;

    // Deposit Modal
    public $showDepositModal = false;
    public $depositAccountId;
    public $depositAmount;
    public $depositDate;
    public $depositDescription;

    protected $rules = [
        'name' => 'required|string|max:255',
        'account_number' => 'nullable|string|max:50',
        'type' => 'required|string',
        'current_balance' => 'numeric',
        'description' => 'nullable|string|max:500',
    ];

    public function render()
    {
        $accounts = PaymentAccount::query()
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('account_number', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        // Calculate Unlinked Payments
        // 1. Expenses without payment_account_id
        $unlinkedExpenses = Expenses::whereNull('payment_account_id')->where('payment_status', 'paid')->count();
        
        // 2. Payments (Orders) without payment_account_id
        $unlinkedOrderPayments = Payment::whereNull('payment_account_id')->count();

        $unlinkedCount = $unlinkedExpenses + $unlinkedOrderPayments;

        return view('inventory::livewire.payment-account.account-list', [
            'accounts' => $accounts,
            'unlinkedCount' => $unlinkedCount
        ]);
    }

    // --- CRUD ---

    public function create()
    {
        $this->reset(['accountId', 'name', 'account_number', 'type', 'current_balance', 'description']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $account = PaymentAccount::findOrFail($id);
        $this->accountId = $id;
        $this->name = $account->name;
        $this->account_number = $account->account_number;
        $this->type = $account->type;
        $this->current_balance = $account->current_balance;
        $this->description = $account->description;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        PaymentAccount::updateOrCreate(
            ['id' => $this->accountId],
            [
                'name' => $this->name,
                'account_number' => $this->account_number,
                'type' => $this->type,
                'current_balance' => $this->current_balance,
                'description' => $this->description,
                'branch_id' => branch()->id ?? null,
            ]
        );

        $this->showModal = false;
        $this->alert('success', $this->accountId ? 'Account updated' : 'Account created');
    }

    public function toggleStatus($id)
    {
        $account = PaymentAccount::findOrFail($id);
        $account->is_active = !$account->is_active;
        $account->save();
        $this->alert('success', 'Account status updated');
    }

    // --- TRANSFER ---

    public function openTransferModal()
    {
        $this->reset(['transferFromId', 'transferToId', 'transferAmount', 'transferDescription']);
        $this->transferDate = now()->format('Y-m-d\TH:i');
        $this->showTransferModal = true;
    }

    public function saveTransfer()
    {
        $this->validate([
            'transferFromId' => 'required|exists:payment_accounts,id',
            'transferToId' => 'required|exists:payment_accounts,id|different:transferFromId',
            'transferAmount' => 'required|numeric|min:0.01',
            'transferDate' => 'required|date',
        ]);

        DB::transaction(function () {
            $from = PaymentAccount::find($this->transferFromId);
            $to = PaymentAccount::find($this->transferToId);

            // Deduct from Source
            $from->decrement('current_balance', $this->transferAmount);
            // Add to Destination
            $to->increment('current_balance', $this->transferAmount);

            // Create Transfer Record
            $transfer = AccountTransfer::create([
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'amount' => $this->transferAmount,
                'transfer_date' => $this->transferDate,
                'description' => $this->transferDescription,
                'added_by' => Auth::id(),
            ]);

            // Log Transactions
            AccountTransaction::create([
                'payment_account_id' => $from->id,
                'amount' => $this->transferAmount,
                'type' => 'credit', // Out
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->id,
                'description' => 'Transfer to ' . $to->name,
                'transaction_date' => $this->transferDate,
            ]);

            AccountTransaction::create([
                'payment_account_id' => $to->id,
                'amount' => $this->transferAmount,
                'type' => 'debit', // In
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->id,
                'description' => 'Transfer from ' . $from->name,
                'transaction_date' => $this->transferDate,
            ]);
        });

        $this->showTransferModal = false;
        $this->alert('success', 'Fund transfer successful');
    }

    // --- DEPOSIT ---

    public function openDepositModal($accountId = null)
    {
        $this->reset(['depositAccountId', 'depositAmount', 'depositDescription']);
        $this->depositAccountId = $accountId;
        $this->depositDate = now()->format('Y-m-d\TH:i');
        $this->showDepositModal = true;
    }

    public function saveDeposit()
    {
        $this->validate([
            'depositAccountId' => 'required|exists:payment_accounts,id',
            'depositAmount' => 'required|numeric|min:0.01',
            'depositDate' => 'required|date',
        ]);

        DB::transaction(function () {
            $account = PaymentAccount::find($this->depositAccountId);
            $account->increment('current_balance', $this->depositAmount);

            // Create "Deposit" Transfer Record (Null From)
            $transfer = AccountTransfer::create([
                'from_account_id' => null,
                'to_account_id' => $account->id,
                'amount' => $this->depositAmount,
                'transfer_date' => $this->depositDate,
                'description' => 'Manual Deposit: ' . $this->depositDescription,
                'added_by' => Auth::id(),
            ]);

            // Log Transaction
            AccountTransaction::create([
                'payment_account_id' => $account->id,
                'amount' => $this->depositAmount,
                'type' => 'debit', // In
                'reference_type' => get_class($transfer),
                'reference_id' => $transfer->id,
                'description' => 'Deposit: ' . $this->depositDescription,
                'transaction_date' => $this->depositDate,
            ]);
        });

        $this->showDepositModal = false;
        $this->alert('success', 'Deposit successful');
    }
}
