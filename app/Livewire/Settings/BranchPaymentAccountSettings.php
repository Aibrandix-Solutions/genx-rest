<?php

namespace App\Livewire\Settings;

use App\Models\Branch;
use App\Models\BranchPaymentAccountSetting;
use Modules\Inventory\Entities\PaymentAccount;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BranchPaymentAccountSettings extends Component
{
    use LivewireAlert;

    public Branch $branch;
    public $settings = [];
    public $paymentMethods = [];
    public $paymentAccounts = [];

    public function mount()
    {
        $this->branch = Branch::where('id', branch()->id)->firstOrFail();
        $this->loadPaymentMethods();
        $this->loadPaymentAccounts();
        $this->loadSettings();
    }

    public function loadPaymentMethods()
    {
        // Common payment methods in the system
        $this->paymentMethods = [
            'cash' => 'Cash',
            'card' => 'Card',
            'upi' => 'UPI',
            'bank_transfer' => 'Bank Transfer',
            'stripe' => 'Stripe',
            'razorpay' => 'Razorpay',
            'paypal' => 'PayPal',
            'flutterwave' => 'Flutterwave',
            'paystack' => 'PayStack',
            'payfast' => 'PayFast',
            'xendit' => 'Xendit',
            'qr_code' => 'QR Code',
            'offline' => 'Offline Payment',
            'due' => 'Due/Credit',
        ];
    }

    public function loadPaymentAccounts()
    {
        // Load payment accounts for this branch and restaurant-level accounts
        $this->paymentAccounts = PaymentAccount::where(function($query) {
            $query->where('branch_id', $this->branch->id)
                  ->orWhereNull('branch_id'); // Restaurant-level accounts (branch_id is null)
        })
        ->where('is_active', true)
        ->orderBy('name')
        ->get()
        ->mapWithKeys(function($account) {
            $displayName = $account->name;
            if ($account->account_number) {
                $displayName .= ' (' . $account->account_number . ')';
            }
            if ($account->branch_id === null) {
                $displayName .= ' [Restaurant]';
            }
            return [$account->id => $displayName];
        })
        ->toArray();
    }

    public function loadSettings()
    {
        // Load existing settings for this branch
        $existingSettings = BranchPaymentAccountSetting::where('branch_id', $this->branch->id)
            ->get()
            ->keyBy('payment_method');

        // Initialize settings array with all payment methods
        foreach ($this->paymentMethods as $method => $label) {
            $this->settings[$method] = $existingSettings->get($method)?->payment_account_id ?? null;
        }
    }

    public function saveSettings()
    {
        try {
            foreach ($this->settings as $paymentMethod => $paymentAccountId) {
                if (empty($paymentAccountId)) {
                    // Remove setting if no account selected
                    BranchPaymentAccountSetting::where('branch_id', $this->branch->id)
                        ->where('payment_method', $paymentMethod)
                        ->delete();
                } else {
                    // Update or create setting
                    BranchPaymentAccountSetting::updateOrCreate(
                        [
                            'branch_id' => $this->branch->id,
                            'payment_method' => $paymentMethod,
                        ],
                        [
                            'payment_account_id' => $paymentAccountId,
                        ]
                    );
                }
            }

            $this->alert('success', __('messages.settingsUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        } catch (\Exception $e) {
            $this->alert('error', 'Failed to save settings: ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.settings.branch-payment-account-settings');
    }
}

