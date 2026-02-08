<?php

namespace Modules\Hotel\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\HotelSetting;
use App\Helper\Files;

class HotelSettingsPage extends Component
{
    use WithFileUploads, LivewireAlert;

    // Hotel Identity
    public $hotel_name = '';
    public $hotel_logo;          // Livewire temp upload
    public $existing_logo = '';   // Current saved logo path

    // Check-in / Check-out
    public $default_check_in_time = '14:00';
    public $default_checkout_time = '12:00';
    public $early_checkin_charge_per_hour = 0;
    public $late_checkout_charge_per_hour = 0;

    // Payment Policy
    public $payment_policy = 'pay_at_checkout';
    public $deposit_percentage = 0;
    public $cancellation_policy = '';

    // Features
    public $enable_room_service = true;
    public $enable_housekeeping_module = true;
    public $enable_dynamic_pricing = false;

    // Tax & Charges
    public $tax_rate = 0;
    public $service_charge_rate = 0;

    public function mount()
    {
        abort_unless(user_can('manage_hotel_settings'), 403);
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $branchId = auth()->user()->branch_id ?? 1;
        $settings = HotelSetting::where('branch_id', $branchId)->first();

        if ($settings) {
            $this->hotel_name = $settings->hotel_name ?? '';
            $this->existing_logo = $settings->hotel_logo ?? '';
            $this->default_check_in_time = substr($settings->default_check_in_time ?? '14:00', 0, 5);
            $this->default_checkout_time = substr($settings->default_checkout_time ?? '12:00', 0, 5);
            $this->early_checkin_charge_per_hour = $settings->early_checkin_charge_per_hour ?? 0;
            $this->late_checkout_charge_per_hour = $settings->late_checkout_charge_per_hour ?? 0;
            $this->payment_policy = $settings->payment_policy ?? 'pay_at_checkout';
            $this->deposit_percentage = $settings->deposit_percentage ?? 0;
            $this->cancellation_policy = $settings->cancellation_policy ?? '';
            $this->enable_room_service = (bool) $settings->enable_room_service;
            $this->enable_housekeeping_module = (bool) $settings->enable_housekeeping_module;
            $this->enable_dynamic_pricing = (bool) $settings->enable_dynamic_pricing;
            $this->tax_rate = $settings->tax_rate ?? 0;
            $this->service_charge_rate = $settings->service_charge_rate ?? 0;
        }
    }

    public function save()
    {
        abort_unless(user_can('manage_hotel_settings'), 403);

        $this->validate([
            'hotel_name' => 'required|string|max:255',
            'hotel_logo' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'default_check_in_time' => 'required|date_format:H:i',
            'default_checkout_time' => 'required|date_format:H:i',
            'early_checkin_charge_per_hour' => 'required|numeric|min:0',
            'late_checkout_charge_per_hour' => 'required|numeric|min:0',
            'payment_policy' => 'required|in:full_advance,partial_deposit,pay_at_checkout',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
            'cancellation_policy' => 'nullable|string|max:2000',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'service_charge_rate' => 'required|numeric|min:0|max:100',
        ]);

        $branchId = auth()->user()->branch_id ?? 1;

        $settings = HotelSetting::updateOrCreate(
            ['branch_id' => $branchId],
            [
                'hotel_name' => $this->hotel_name,
                'default_check_in_time' => $this->default_check_in_time,
                'default_checkout_time' => $this->default_checkout_time,
                'early_checkin_charge_per_hour' => $this->early_checkin_charge_per_hour,
                'late_checkout_charge_per_hour' => $this->late_checkout_charge_per_hour,
                'payment_policy' => $this->payment_policy,
                'deposit_percentage' => $this->payment_policy === 'partial_deposit' ? $this->deposit_percentage : null,
                'cancellation_policy' => $this->cancellation_policy ?: null,
                'enable_room_service' => $this->enable_room_service,
                'enable_housekeeping_module' => $this->enable_housekeeping_module,
                'enable_dynamic_pricing' => $this->enable_dynamic_pricing,
                'tax_rate' => $this->tax_rate,
                'service_charge_rate' => $this->service_charge_rate,
            ]
        );

        // Handle logo upload
        if ($this->hotel_logo) {
            // Delete old logo if exists
            if ($settings->hotel_logo) {
                Files::deleteFile($settings->hotel_logo, 'hotel-logo');
            }

            $settings->hotel_logo = Files::uploadLocalOrS3($this->hotel_logo, 'hotel-logo', 300, 300);
            $settings->save();
            $this->existing_logo = $settings->hotel_logo;
            $this->hotel_logo = null;
        }

        $this->alert('success', __('hotel::modules.settings.saved'));
    }

    public function removeLogo()
    {
        abort_unless(user_can('manage_hotel_settings'), 403);

        $branchId = auth()->user()->branch_id ?? 1;
        $settings = HotelSetting::where('branch_id', $branchId)->first();

        if ($settings && $settings->hotel_logo) {
            Files::deleteFile($settings->hotel_logo, 'hotel-logo');
            $settings->update(['hotel_logo' => null]);
            $this->existing_logo = '';
        }

        $this->alert('success', 'Logo removed.');
    }

    public function render()
    {
        return view('hotel::livewire.settings.hotel-settings')->layout('layouts.app');
    }
}
