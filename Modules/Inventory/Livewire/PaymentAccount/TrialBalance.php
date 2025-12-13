<?php

namespace Modules\Inventory\Livewire\PaymentAccount;

use Livewire\Component;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrialBalance extends Component
{
    public $startDate;
    public $endDate;
    public $branchId;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function render()
    {
        $query = PaymentAccount::query();

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $accounts = $query->get();
        
        $accounts->map(function($account) {
            // Opening Balance: All transactions strictly BEFORE start date
            $opening = AccountTransaction::where('payment_account_id', $account->id)
                ->whereDate('transaction_date', '<', $this->startDate)
                ->sum(DB::raw("CASE WHEN type='debit' THEN amount ELSE -amount END"));
            
            // Period Debit (In)
            $debit = AccountTransaction::where('payment_account_id', $account->id)
                ->whereDate('transaction_date', '>=', $this->startDate)
                ->whereDate('transaction_date', '<=', $this->endDate)
                ->where('type', 'debit')
                ->sum('amount');
            
            // Period Credit (Out)
            $credit = AccountTransaction::where('payment_account_id', $account->id)
                ->whereDate('transaction_date', '>=', $this->startDate)
                ->whereDate('transaction_date', '<=', $this->endDate)
                ->where('type', 'credit')
                ->sum('amount');
            
            $account->opening_balance = $opening;
            $account->period_debit = $debit;
            $account->period_credit = $credit;
            $account->closing_balance = $opening + $debit - $credit;
            
            return $account;
        });

        $totalOpening = $accounts->sum('opening_balance');
        $totalDebit = $accounts->sum('period_debit');
        $totalCredit = $accounts->sum('period_credit');
        $totalClosing = $accounts->sum('closing_balance');

        return view('inventory::livewire.payment-account.trial-balance', [
            'accounts' => $accounts,
            'totalOpening' => $totalOpening,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'totalClosing' => $totalClosing,
            'branches' => Branch::all()
        ]);
    }
}
