<?php

namespace Modules\Inventory\Livewire;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\BranchPaymentAccountSetting;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class RecordPurchasePayment extends Component
{
    use LivewireAlert;

    public $purchaseId;
    public $purchase = null;
    public $paymentAmount;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentAccountId;
    public $paymentNote = '';
    public $paymentMethods = ['cash', 'card', 'bank_transfer', 'cheque', 'other'];
    public $paymentAccounts = [];

    protected function rules()
    {
        return [
            'paymentAmount' => 'required|numeric|min:0.01|max:' . ($this->purchase->due_amount ?? 999999999),
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|in:cash,card,bank_transfer,cheque,other',
            'paymentAccountId' => 'nullable|exists:payment_accounts,id',
            'paymentNote' => 'nullable|string',
        ];
    }

    protected $messages = [
        'paymentAmount.required' => 'Payment amount is required',
        'paymentAmount.numeric' => 'Payment amount must be a number',
        'paymentAmount.min' => 'Payment amount must be greater than 0',
        'paymentAmount.max' => 'Payment amount cannot exceed the due amount',
        'paymentDate.required' => 'Payment date is required',
        'paymentDate.date' => 'Payment date must be a valid date',
        'paymentMethod.required' => 'Payment method is required',
        'paymentMethod.in' => 'Invalid payment method selected',
        'paymentAccountId.exists' => 'Selected payment account does not exist',
    ];

    public function mount($purchaseId)
    {
        $this->purchaseId = $purchaseId;
        $this->purchase = PurchaseOrder::findOrFail($purchaseId);
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentAmount = $this->purchase->due_amount;

        // Load payment accounts (branch scoped)
        try {
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

    public function recordPayment()
    {
        $this->validate();

        try {
            $paidOn = $this->paymentDate ?: now();
            $paymentData = [
                'supplier_id' => $this->purchase->supplier_id,
                'purchase_order_id' => $this->purchase->id,
                'amount' => $this->paymentAmount,
                'paid_on' => $paidOn,
                'payment_method' => $this->paymentMethod,
                'note' => $this->paymentNote,
                'added_by' => user()->id,
            ];
            
            // Add payment_account_id only if set and payment_accounts table exists
            if ($this->paymentAccountId) {
                $paymentData['payment_account_id'] = $this->paymentAccountId;
            }

            $payment = SupplierPayment::create($paymentData);

            // Update Payment Account Balance and log transaction if account selected
            if ($this->paymentAccountId) {
                $account = PaymentAccount::find($this->paymentAccountId);
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

            $this->alert('success', 'Payment recorded successfully!');
            $this->dispatch('paymentRecorded');
            $this->dispatch('refreshPurchaseList');
            $this->resetForm();
        } catch (\Exception $e) {
            $this->alert('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->paymentAmount = null;
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethod = 'cash';
        $this->paymentAccountId = null;
        $this->paymentNote = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('inventory::livewire.record-purchase-payment');
    }
}
