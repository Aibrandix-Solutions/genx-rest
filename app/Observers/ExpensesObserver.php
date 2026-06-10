<?php

namespace App\Observers;

use App\Models\BranchPaymentAccountSetting;
use App\Models\Expenses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Entities\AccountTransaction;
use Modules\Inventory\Entities\PaymentAccount;

class ExpensesObserver
{
    public function creating(Expenses $expenses)
    {
        if (branch()) {
            $expenses->branch_id = branch()->id;
        }

        $this->applyDefaultPaymentAccount($expenses);
    }

    public function updating(Expenses $expenses)
    {
        if ($expenses->payment_status !== 'paid') {
            $expenses->payment_account_id = null;

            return;
        }

        if ($expenses->isDirty('payment_method') || !$expenses->payment_account_id) {
            $this->applyDefaultPaymentAccount($expenses);
        }
    }

    public function created(Expenses $expenses)
    {
        if ($expenses->payment_status === 'paid') {
            $this->applyAccountTransaction($expenses);
        }
    }

    public function updated(Expenses $expenses)
    {
        if (!$expenses->wasChanged(['payment_status', 'payment_account_id', 'amount', 'payment_method'])) {
            return;
        }

        try {
            DB::transaction(function () use ($expenses) {
                $wasPaid = $expenses->getOriginal('payment_status') === 'paid';
                $isPaid = $expenses->payment_status === 'paid';
                $oldAccountId = $expenses->getOriginal('payment_account_id');
                $oldAmount = (float) $expenses->getOriginal('amount');

                if ($wasPaid && $oldAccountId) {
                    $this->revertAccountTransaction($oldAccountId, $oldAmount, $expenses->id);
                }

                if ($isPaid && $expenses->payment_account_id) {
                    $this->applyAccountTransaction($expenses);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error syncing expense account transaction for expense ' . $expenses->id . ': ' . $e->getMessage());
        }
    }

    public function deleted(Expenses $expenses)
    {
        if ($expenses->payment_status === 'paid' && $expenses->payment_account_id) {
            $this->revertAccountTransaction(
                $expenses->payment_account_id,
                (float) $expenses->amount,
                $expenses->id
            );
        }
    }

    private function applyDefaultPaymentAccount(Expenses $expenses): void
    {
        if ($expenses->payment_status !== 'paid' || !$expenses->payment_method) {
            return;
        }

        $branchId = $expenses->branch_id ?? branch()?->id;

        if (!$branchId) {
            return;
        }

        $expenses->payment_account_id = BranchPaymentAccountSetting::resolveDefaultAccountId(
            $branchId,
            $expenses->payment_method
        );
    }

    private function applyAccountTransaction(Expenses $expense): void
    {
        if (!$expense->payment_account_id) {
            return;
        }

        $exists = AccountTransaction::where('payment_account_id', $expense->payment_account_id)
            ->where('reference_type', get_class($expense))
            ->where('reference_id', $expense->id)
            ->exists();

        if ($exists) {
            return;
        }

        $account = PaymentAccount::find($expense->payment_account_id);

        if (!$account) {
            return;
        }

        $account->decrement('current_balance', $expense->amount);

        AccountTransaction::create([
            'payment_account_id' => $account->id,
            'amount' => $expense->amount,
            'type' => 'credit',
            'reference_type' => get_class($expense),
            'reference_id' => $expense->id,
            'description' => 'Expense: ' . $expense->expense_title,
            'transaction_date' => $expense->payment_date ?? $expense->expense_date ?? now(),
        ]);
    }

    private function revertAccountTransaction(int $accountId, float $amount, int $expenseId): void
    {
        $account = PaymentAccount::find($accountId);

        if ($account) {
            $account->increment('current_balance', $amount);
        }

        AccountTransaction::where('payment_account_id', $accountId)
            ->where('reference_type', Expenses::class)
            ->where('reference_id', $expenseId)
            ->delete();
    }
}
