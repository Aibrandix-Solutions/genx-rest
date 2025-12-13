<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\RewardSetting;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class RewardPointsSettings extends Component
{
    use LivewireAlert;

    public $settings;
    
    // General Settings
    public $enable_reward_point;
    public $reward_point_display_name;
    
    // Earning Settings
    public $amount_spend_for_unit_point;
    public $minimum_order_total_to_earn;
    public $maximum_points_per_order;
    
    // Redeem Settings
    public $redeem_amount_per_unit_point;
    public $minimum_order_total_to_redeem;
    public $minimum_redeem_point;
    public $maximum_redeem_point_per_order;
    public $reward_point_expiry_period;
    public $reward_point_expiry_period_unit;

    public function mount()
    {
        $this->settings = RewardSetting::getForRestaurant(restaurant()->id);
        
        $this->enable_reward_point = $this->settings->enable_reward_point;
        $this->reward_point_display_name = $this->settings->reward_point_display_name;
        $this->amount_spend_for_unit_point = $this->settings->amount_spend_for_unit_point;
        $this->minimum_order_total_to_earn = $this->settings->minimum_order_total_to_earn;
        $this->maximum_points_per_order = $this->settings->maximum_points_per_order;
        $this->redeem_amount_per_unit_point = $this->settings->redeem_amount_per_unit_point;
        $this->minimum_order_total_to_redeem = $this->settings->minimum_order_total_to_redeem;
        $this->minimum_redeem_point = $this->settings->minimum_redeem_point;
        $this->maximum_redeem_point_per_order = $this->settings->maximum_redeem_point_per_order;
        $this->reward_point_expiry_period = $this->settings->reward_point_expiry_period;
        $this->reward_point_expiry_period_unit = $this->settings->reward_point_expiry_period_unit ?? 'month';
    }

    public function submitForm()
    {
        // Convert empty strings to null for nullable fields before validation
        $nullableFields = ['maximum_points_per_order', 'minimum_redeem_point', 'maximum_redeem_point_per_order'];
        foreach ($nullableFields as $field) {
            if ($this->$field === '' || $this->$field === null) {
                $this->$field = null;
            }
        }

        $this->validate([
            'reward_point_display_name' => 'required|string|max:50',
            'amount_spend_for_unit_point' => 'required|numeric|min:0.01',
            'minimum_order_total_to_earn' => 'required|numeric|min:0',
            'maximum_points_per_order' => 'nullable|integer|min:1',
            'redeem_amount_per_unit_point' => 'required|numeric|min:0.01',
            'minimum_order_total_to_redeem' => 'required|numeric|min:0',
            'minimum_redeem_point' => 'nullable|integer|min:1',
            'maximum_redeem_point_per_order' => 'nullable|integer|min:1',
            'reward_point_expiry_period' => 'required|integer|min:0',
            'reward_point_expiry_period_unit' => 'required|in:month,year',
        ]);

        // Convert empty strings to null for nullable fields
        $this->settings->update([
            'enable_reward_point' => $this->enable_reward_point,
            'reward_point_display_name' => $this->reward_point_display_name,
            'amount_spend_for_unit_point' => $this->amount_spend_for_unit_point,
            'minimum_order_total_to_earn' => $this->minimum_order_total_to_earn,
            'maximum_points_per_order' => $this->maximum_points_per_order,
            'redeem_amount_per_unit_point' => $this->redeem_amount_per_unit_point,
            'minimum_order_total_to_redeem' => $this->minimum_order_total_to_redeem,
            'minimum_redeem_point' => $this->minimum_redeem_point,
            'maximum_redeem_point_per_order' => $this->maximum_redeem_point_per_order,
            'reward_point_expiry_period' => $this->reward_point_expiry_period,
            'reward_point_expiry_period_unit' => $this->reward_point_expiry_period_unit,
        ]);

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.settings.reward-points-settings');
    }
}

