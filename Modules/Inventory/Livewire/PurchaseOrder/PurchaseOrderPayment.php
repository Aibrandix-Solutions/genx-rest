<?php

namespace Modules\Inventory\Livewire\PurchaseOrder;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Entities\PaymentAccount;
use Modules\Inventory\Entities\AccountTransaction;
use App\Models\BranchPaymentAccountSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PurchaseOrderPayment extends Component
{
    use WithFileUploads, LivewireAlert;

    public $showModal = false;
    public $purchaseOrder;
    public $isSaving = false;

    // Payment form fields
    public $paymentAmount;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentAccount;
    public $paymentNote;
    public $paymentDocument;
    public $transactionId;

    protected $rules = [
        'paymentAmount' => 'required|numeric|min:0.01',
        'paymentDate' => 'required|date',
        'paymentMethod' => 'required|string',
        'paymentAccount' => 'nullable|exists:payment_accounts,id',
        'paymentNote' => 'nullable|string|max:500',
        'paymentDocument' => 'nullable|file|mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png|max:10240',
        'transactionId' => 'nullable|string|max:255',
    ];

    protected $listeners = ['showPurchaseOrderPaymentModal' => 'show'];

    public function mount()
    {
        $this->paymentDate = now()->format('Y-m-d\TH:i');
    }

    public function show(...$args)
    {
        // Extract ID from event data - can be passed as object or directly
        $id = null;
        if (!empty($args)) {
            $firstArg = $args[0];
            if (is_array($firstArg)) {
                $id = $firstArg['purchaseOrder'] ?? $firstArg['id'] ?? null;
            } elseif (is_numeric($firstArg)) {
                $id = $firstArg;
            } elseif (is_object($firstArg)) {
                $id = $firstArg->purchaseOrder ?? $firstArg->id ?? null;
            }
        }
        
        if (!$id) {
            $this->alert('error', 'Purchase order ID not provided.');
            return;
        }

        $this->purchaseOrder = PurchaseOrder::with('payments')->find($id);
        
        if (!$this->purchaseOrder) {
            $this->alert('error', 'Purchase order not found.');
            return;
        }
        
        $this->resetForm();
        
        // Pre-fill with due amount
        $this->paymentAmount = max(0, (float) $this->purchaseOrder->due_amount);
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->reset(['paymentAmount', 'paymentNote', 'paymentDocument', 'transactionId']);
        $this->paymentMethod = 'cash';
        $this->paymentAccount = BranchPaymentAccountSetting::resolveDefaultAccountId(
            branch()->id,
            $this->paymentMethod
        );
        $this->paymentDate = now()->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    public function updatedPaymentMethod($value)
    {
        if ($value) {
            $this->paymentAccount = BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $value);
        }
    }

    public function updatedPaymentAmount()
    {
        if (!$this->purchaseOrder) {
            return;
        }
        
        $maxAmount = max(0, (float) $this->purchaseOrder->due_amount);
        if ($this->paymentAmount > $maxAmount) {
            $this->paymentAmount = $maxAmount;
            $this->alert('warning', "Payment amount cannot exceed the due amount of " . number_format($maxAmount, 2));
        }
    }

    public function savePayment()
    {
        if ($this->isSaving) {
            return;
        }

        $lock = Cache::lock('po-payment:' . ($this->purchaseOrder?->id) . ':' . auth()->id(), 30);
        if (! $lock->get()) {
            return;
        }

        $this->isSaving = true;

        try {
        $this->validate();

        if (!$this->purchaseOrder) {
            $this->alert('error', 'Purchase order not found.');
            return;
        }

        // Refresh purchase order to get latest payments
        $this->purchaseOrder->load('payments');
        
        // Check if payment amount exceeds due amount
        $dueAmount = max(0, (float) $this->purchaseOrder->due_amount);
        if ($this->paymentAmount > $dueAmount) {
            $this->alert('error', 'Payment amount cannot exceed the due amount.');
            return;
        }

        $path = null;
        if ($this->paymentDocument) {
            $path = $this->paymentDocument->store('supplier-payments', 'public');
        }

        $paymentAccount = $this->paymentAccount
            ?: BranchPaymentAccountSetting::resolveDefaultAccountId(branch()->id, $this->paymentMethod);

        $payment = SupplierPayment::create([
            'supplier_id' => $this->purchaseOrder->supplier_id,
            'payment_batch_id' => (string) Str::uuid(),
            'purchase_order_id' => $this->purchaseOrder->id,
            'payment_account_id' => $paymentAccount,
            'amount' => $this->paymentAmount,
            'paid_on' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'transaction_id' => $this->transactionId,
            'note' => $this->paymentNote,
            'document_path' => $path,
            'added_by' => Auth::id(),
        ]);

        // Update Payment Account Balance if selected and log transaction
        if ($paymentAccount) {
            $account = PaymentAccount::find($paymentAccount);
            if ($account) {
                $account->decrement('current_balance', $this->paymentAmount);

                // Log Transaction
                AccountTransaction::create([
                    'payment_account_id' => $account->id,
                    'amount' => $this->paymentAmount,
                    'type' => 'credit', // Money Out
                    'reference_type' => get_class($payment),
                    'reference_id' => $payment->id,
                    'description' => 'Payment for Purchase Order: ' . $this->purchaseOrder->po_number . ($this->paymentNote ? ' - ' . $this->paymentNote : ''),
                    'transaction_date' => $this->paymentDate,
                ]);
            }
        }

        $this->alert('success', 'Payment recorded successfully');
        $this->showModal = false;
        $this->dispatch('purchaseOrderPaymentSaved');
        $this->dispatch('paymentRecorded');
        $this->dispatch('refreshPurchaseList');
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

    public function render()
    {
        return view('inventory::livewire.purchase-order.purchase-order-payment', [
            'paymentAccounts' => PaymentAccount::where('branch_id', branch()->id)->get(),
            'paymentMethods' => ['cash', 'card', 'bank_transfer', 'upi', 'cheque', 'other'],
        ]);
    }
}

