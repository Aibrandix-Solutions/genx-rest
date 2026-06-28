<?php

namespace Modules\Inventory\Livewire;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\SupplierPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
    public bool $isSaving = false;

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

        $this->paymentAccountId = BranchPaymentAccountSetting::resolveDefaultAccountId(
            branch()->id,
            $this->paymentMethod
        );
    }

    public function updatedPaymentMethod($value)
    {
        if ($value) {
            $this->paymentAccountId = BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $value);
        }
    }

    public function updatedPaymentAmount()
    {
        if (! $this->purchase) {
            return;
        }

        $maxAmount = max(0, (float) $this->purchase->due_amount);
        if ($this->paymentAmount > $maxAmount) {
            $this->paymentAmount = $maxAmount;
            $this->alert('warning', 'Payment amount cannot exceed the due amount of ' . number_format($maxAmount, 2));
        }
    }

    public function recordPayment()
    {
        if ($this->isSaving) {
            return;
        }

        $lock = Cache::lock('po-payment:' . ($this->purchase?->id) . ':' . Auth::id(), 30);
        if (! $lock->get()) {
            return;
        }

        $this->isSaving = true;

        try {
            $this->validate();

            $this->purchase = PurchaseOrder::with('payments')->findOrFail($this->purchaseId);

            $dueAmount = max(0, (float) $this->purchase->due_amount);
            if ($this->paymentAmount > $dueAmount) {
                $this->alert('error', 'Payment amount cannot exceed the due amount.');
                return;
            }

            $paidOn = $this->paymentDate ?: now();
            $paymentAccountId = $this->paymentAccountId
                ?: BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $this->paymentMethod);

            $paymentData = [
                'supplier_id' => $this->purchase->supplier_id,
                'payment_batch_id' => (string) Str::uuid(),
                'purchase_order_id' => $this->purchase->id,
                'amount' => $this->paymentAmount,
                'paid_on' => $paidOn,
                'payment_method' => $this->paymentMethod,
                'note' => $this->paymentNote,
                'added_by' => Auth::id(),
            ];

            if ($paymentAccountId) {
                $paymentData['payment_account_id'] = $paymentAccountId;
            }

            $payment = SupplierPayment::create($paymentData);

            if ($paymentAccountId) {
                $account = PaymentAccount::find($paymentAccountId);
                if ($account) {
                    $account->decrement('current_balance', $this->paymentAmount);

                    AccountTransaction::create([
                        'payment_account_id' => $account->id,
                        'amount' => $this->paymentAmount,
                        'type' => 'credit',
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
            $this->dispatch('purchaseOrderPaymentSaved');
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', 'Failed to record payment: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
            $lock->release();
        }
    }

    public function resetForm()
    {
        $this->paymentAmount = null;
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->paymentMethod = 'cash';
        $this->paymentAccountId = BranchPaymentAccountSetting::resolveDefaultAccountId(
            branch()->id,
            $this->paymentMethod
        );
        $this->paymentNote = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('inventory::livewire.record-purchase-payment');
    }
}
