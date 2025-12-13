<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\RewardBalance;
use App\Models\RewardTransaction;
use App\Models\RewardSetting;
use Livewire\WithPagination;
use App\Services\RewardPointsService;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class CustomerRewardPoints extends Component
{
    use WithPagination, LivewireAlert;

    public $customer;
    public $balance;
    public $settings;
    public $showAdjustModal = false;
    public $adjustPoints = 0;
    public $adjustDescription = '';

    public function mount($customer)
    {
        $this->customer = $customer;
        $this->loadData();
    }

    public function loadData()
    {
        $this->balance = RewardBalance::getForCustomer($this->customer->id, restaurant()->id);
        $this->settings = RewardSetting::getForRestaurant(restaurant()->id);
    }

    public function adjustPoints()
    {
        $this->validate([
            'adjustPoints' => 'required|integer',
            'adjustDescription' => 'nullable|string|max:255',
        ]);

        if ($this->adjustPoints == 0) {
            $this->alert('error', __('Please enter a non-zero points amount'), ['toast' => true]);
            return;
        }

        try {
            $rewardService = app(RewardPointsService::class);
            $rewardService->adjustPoints(
                $this->customer,
                $this->adjustPoints,
                $this->adjustDescription ?: null,
                restaurant()->id
            );

            $this->alert('success', __('Points adjusted successfully'), ['toast' => true]);
            $this->showAdjustModal = false;
            $this->adjustPoints = 0;
            $this->adjustDescription = '';
            $this->loadData();
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage(), ['toast' => true]);
        }
    }

    public function render()
    {
        $transactions = RewardTransaction::where('customer_id', $this->customer->id)
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.customer.customer-reward-points', [
            'transactions' => $transactions,
        ]);
    }
}

