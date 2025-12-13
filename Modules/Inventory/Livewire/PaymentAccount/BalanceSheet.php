<?php

namespace Modules\Inventory\Livewire\PaymentAccount;

use Livewire\Component;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\SupplierPayment;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceSheet extends Component
{
    public $date;
    public $branchId;

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        // Optional: Set default branch if user is assigned to one
        if (request()->has('branch_id')) {
            $this->branchId = request()->get('branch_id');
        }
    }

    public function render()
    {
        // 1. Calculate Assets (Payment Accounts)
        $accountQuery = PaymentAccount::query();

        if ($this->branchId) {
            $accountQuery->where('branch_id', $this->branchId);
        }

        $accounts = $accountQuery->get();
        
        $accounts->map(function($account) {
            $balance = AccountTransaction::where('payment_account_id', $account->id)
                ->whereDate('transaction_date', '<=', $this->date)
                ->sum(DB::raw("CASE WHEN type='debit' THEN amount ELSE -amount END"));
            
            $account->computed_balance = $balance;
            return $account;
        });

        $totalAssets = $accounts->sum('computed_balance');


        // 2. Calculate Liabilities (Accounts Payable / Supplier Dues)
        // Liability = Total Purchases (Received POs) - Total Payments (Supplier Payments)
        
        // A. Total Purchases (Liability Increases)
        $poQuery = PurchaseOrder::where('status', 'received')
            ->whereDate('order_date', '<=', $this->date);
            
        if ($this->branchId) {
            $poQuery->where('branch_id', $this->branchId);
        }
        
        $totalPurchases = $poQuery->sum('total_amount');

        // B. Total Payments (Liability Decreases)
        $paymentQuery = SupplierPayment::whereDate('paid_on', '<=', $this->date);

        if ($this->branchId) {
            $paymentQuery->whereHas('account', function($q) {
                $q->where('branch_id', $this->branchId);
            });
        }

        $totalPaid = $paymentQuery->sum('amount');

        $totalLiabilities = max(0, $totalPurchases - $totalPaid);
        
        // Calculate Equity (Assets - Liabilities)
        $totalEquity = $totalAssets - $totalLiabilities;

        return view('inventory::livewire.payment-account.balance-sheet', [
            'accounts' => $accounts,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'branches' => Branch::all()
        ]);
    }
}
