<?php

namespace Modules\Hotel\Livewire\Pricing;

use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Entities\RoomPrice;
use Modules\Hotel\Entities\HotelSetting;
use Carbon\Carbon;

class RoomPricingManager extends Component
{
    use LivewireAlert;

    public $selectedRoomTypeId = null;
    public $roomTypes = [];
    public $prices = [];
    
    // Form fields for adding/editing pricing
    public $showPricingModal = false;
    public $editingPricingId = null;
    public $dateFrom = '';
    public $dateTo = '';
    public $price = 0;
    public $reason = '';
    
    // Settings
    public $isDynamicPricingEnabled = false;

    public function mount()
    {
        abort_unless(user_can('manage_room_pricing'), 403);

        // Respect the enable_dynamic_pricing hotel setting
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();
        if ($settings && !$settings->enable_dynamic_pricing) {
            abort(403, 'Dynamic pricing is disabled in Hotel Settings.');
        }

        $this->loadRoomTypes();
        $this->loadSettings();
    }

    public function loadRoomTypes()
    {
        $this->roomTypes = RoomType::where('restaurant_id', restaurant()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($this->roomTypes->isNotEmpty() && !$this->selectedRoomTypeId) {
            $this->selectedRoomTypeId = $this->roomTypes->first()->id;
        }

        if ($this->selectedRoomTypeId) {
            $this->loadPrices();
        }
    }

    public function loadSettings()
    {
        $settings = HotelSetting::where('restaurant_id', restaurant()->id)->first();
        $this->isDynamicPricingEnabled = $settings ? (bool) $settings->enable_dynamic_pricing : false;
    }

    public function loadPrices()
    {
        if (!$this->selectedRoomTypeId) {
            return;
        }

        $this->prices = RoomPrice::where('room_type_id', $this->selectedRoomTypeId)
            ->orderBy('date_from', 'asc')
            ->get();
    }

    public function selectRoomType($roomTypeId)
    {
        $this->selectedRoomTypeId = $roomTypeId;
        $this->loadPrices();
    }

    public function openPricingModal()
    {
        abort_unless(user_can('edit_room_pricing'), 403);

        if (!$this->isDynamicPricingEnabled) {
            $this->alert('warning', 'Dynamic pricing is not enabled. Please enable it in Hotel Settings first.');
            return;
        }

        $this->resetForm();
        $this->showPricingModal = true;
    }

    public function editPricing($id)
    {
        abort_unless(user_can('edit_room_pricing'), 403);

        $pricing = RoomPrice::find($id);
        if ($pricing) {
            $this->editingPricingId = $id;
            $this->dateFrom = $pricing->date_from->format('Y-m-d');
            $this->dateTo = $pricing->date_to->format('Y-m-d');
            $this->price = $pricing->price;
            $this->reason = $pricing->reason;
            $this->showPricingModal = true;
        }
    }

    public function savePricing()
    {
        abort_unless(user_can('edit_room_pricing'), 403);

        $this->validate([
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date|after_or_equal:dateFrom',
            'price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $data = [
            'restaurant_id' => restaurant()->id,
            'room_type_id' => $this->selectedRoomTypeId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'price' => $this->price,
            'reason' => $this->reason,
        ];

        if ($this->editingPricingId) {
            $pricing = RoomPrice::find($this->editingPricingId);
            $pricing->update($data);
            $this->alert('success', 'Room pricing updated successfully');
        } else {
            RoomPrice::create($data);
            $this->alert('success', 'Room pricing added successfully');
        }

        $this->showPricingModal = false;
        $this->resetForm();
        $this->loadPrices();
    }

    public function confirmDeletePricing($id)
    {
        abort_unless(user_can('delete_room_pricing'), 403);

        $this->alert('warning', 'Are you sure you want to delete this pricing override?', [
            'showConfirmButton' => true,
            'showCancelButton' => true,
            'confirmButtonText' => 'Yes, Delete',
            'cancelButtonText' => 'Cancel',
            'onConfirmed' => 'deletePricingConfirmed',
            'onDismissed' => 'dismissAlert',
            'data' => ['id' => $id],
        ]);
    }

    #[On('deletePricingConfirmed')]
    public function deletePricing($id)
    {
        abort_unless(user_can('delete_room_pricing'), 403);

        RoomPrice::find($id)?->delete();
        $this->alert('success', 'Pricing override deleted successfully');
        $this->loadPrices();
    }

    private function resetForm()
    {
        $this->editingPricingId = null;
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->price = 0;
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('hotel::livewire.pricing.room-pricing-manager')->layout('layouts.app');
    }
}
