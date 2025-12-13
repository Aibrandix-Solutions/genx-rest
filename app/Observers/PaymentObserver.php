<?php

namespace App\Observers;

use App\Models\Payment;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{

    public function creating(Payment $payment)
    {
        if (branch()) {
            $payment->branch_id = branch()->id;
        }
    }

    public function created(Payment $payment)
    {
        // Automatically create AccountTransaction if payment has payment_account_id
        if ($payment->payment_account_id) {
            try {
                DB::transaction(function () use ($payment) {
                    $account = PaymentAccount::find($payment->payment_account_id);
                    
                    if ($account) {
                        // Skip 'due' payments - they don't represent actual money received
                        if ($payment->payment_method !== 'due') {
                            // Increment account balance (money in)
                            $account->increment('current_balance', $payment->amount);
                            
                            // Create transaction record
                            AccountTransaction::create([
                                'payment_account_id' => $account->id,
                                'amount' => $payment->amount,
                                'type' => 'debit', // Money in (income)
                                'reference_type' => get_class($payment),
                                'reference_id' => $payment->id,
                                'description' => 'Order Payment #' . ($payment->order->order_number ?? $payment->order_id) . ' (' . ucfirst($payment->payment_method) . ')',
                                'transaction_date' => $payment->created_at ?? now(),
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error creating account transaction for payment ' . $payment->id . ': ' . $e->getMessage());
                // Don't throw - payment should still be created even if transaction fails
            }
        }
    }

    public function updated(Payment $payment)
    {
        // Handle payment_account_id changes
        if ($payment->isDirty('payment_account_id') || $payment->isDirty('amount')) {
            try {
                DB::transaction(function () use ($payment) {
                    $oldAccountId = $payment->getOriginal('payment_account_id');
                    $newAccountId = $payment->payment_account_id;
                    $oldAmount = $payment->getOriginal('amount');
                    $newAmount = $payment->amount;
                    
                    // Revert old account transaction if account changed
                    if ($oldAccountId && $oldAccountId != $newAccountId) {
                        $oldAccount = PaymentAccount::find($oldAccountId);
                        if ($oldAccount && $payment->payment_method !== 'due') {
                            // Revert balance
                            $oldAccount->decrement('current_balance', $oldAmount);
                            
                            // Delete old transaction
                            AccountTransaction::where('payment_account_id', $oldAccountId)
                                ->where('reference_type', get_class($payment))
                                ->where('reference_id', $payment->id)
                                ->delete();
                        }
                    }
                    
                    // Create/update new account transaction
                    if ($newAccountId && $payment->payment_method !== 'due') {
                        $newAccount = PaymentAccount::find($newAccountId);
                        
                        if ($newAccount) {
                            // Check if transaction already exists
                            $existingTransaction = AccountTransaction::where('payment_account_id', $newAccountId)
                                ->where('reference_type', get_class($payment))
                                ->where('reference_id', $payment->id)
                                ->first();
                            
                            if ($existingTransaction) {
                                // Update existing transaction
                                $amountDiff = $newAmount - $oldAmount;
                                $newAccount->increment('current_balance', $amountDiff);
                                $existingTransaction->update([
                                    'amount' => $newAmount,
                                    'description' => 'Order Payment #' . ($payment->order->order_number ?? $payment->order_id) . ' (' . ucfirst($payment->payment_method) . ')',
                                ]);
                            } else {
                                // Create new transaction
                                $newAccount->increment('current_balance', $newAmount);
                                AccountTransaction::create([
                                    'payment_account_id' => $newAccount->id,
                                    'amount' => $newAmount,
                                    'type' => 'debit',
                                    'reference_type' => get_class($payment),
                                    'reference_id' => $payment->id,
                                    'description' => 'Order Payment #' . ($payment->order->order_number ?? $payment->order_id) . ' (' . ucfirst($payment->payment_method) . ')',
                                    'transaction_date' => $payment->created_at ?? now(),
                                ]);
                            }
                        }
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error updating account transaction for payment ' . $payment->id . ': ' . $e->getMessage());
            }
        }
    }

    public function deleting(Payment $payment)
    {
        // Revert account transaction when payment is deleted
        if ($payment->payment_account_id && $payment->payment_method !== 'due') {
            try {
                DB::transaction(function () use ($payment) {
                    $account = PaymentAccount::find($payment->payment_account_id);
                    
                    if ($account) {
                        // Revert balance (decrement)
                        $account->decrement('current_balance', $payment->amount);
                        
                        // Delete transaction record
                        AccountTransaction::where('payment_account_id', $payment->payment_account_id)
                            ->where('reference_type', get_class($payment))
                            ->where('reference_id', $payment->id)
                            ->delete();
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error reverting account transaction for payment ' . $payment->id . ': ' . $e->getMessage());
            }
        }
    }
}
