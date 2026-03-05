<?php

namespace Modules\Kitchen\Livewire;

use Livewire\Component;
use App\Models\MenuItem;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\MultipleKot;
use Livewire\Attributes\On;

class AddItemToKitchen extends Component
{
    use LivewireAlert;
    public $search = '';
    public $selectedItems = [];
    public $kitchenId;
    public $kitchen;

    public function mount()
    {
        $kitchenId = $this->kitchenId;
    }

    #[On('preSelectItem')]
    public function preSelectItem($itemId)
    {
        if (!in_array($itemId, $this->selectedItems)) {
            $this->selectedItems[] = $itemId;
        }
    }

    public function addItems()
    {
        $this->validate([
            'selectedItems' => 'required|array|min:1',
        ]);

        $items = MenuItem::whereIn('id', $this->selectedItems)->get();

        foreach ($items as $item) {
            // Set legacy kot_place_id if not already set
            if (!$item->kot_place_id) {
                $item->kot_place_id = $this->kitchenId;
                $item->save();
            }

            // Sync pivot table (add this kitchen without removing others)
            $existingIds = $item->kotPlaces()->pluck('kot_places.id')->toArray();
            if (!in_array($this->kitchenId, $existingIds)) {
                $item->kotPlaces()->attach($this->kitchenId, [
                    'is_primary' => empty($existingIds), // First kitchen is primary
                ]);
            }
        }

        $this->reset('selectedItems', 'search');
        $this->dispatch('hideItemToKitchen');
        $this->alert('success', __('kitchen::messages.ItemAddedToKitchen'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);
    }

    public function toggleItemStatus($itemId)
    {
        $item = MenuItem::findOrFail($itemId);

        $item->kot_place_id = null;
        $item->save();

        $this->dispatch('$refresh');

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('kitchen::livewire.add-item-to-kitchen', [
            'items' => $this->items, // use the computed property
            'fetchedItems' => MenuItem::where('kot_place_id', $this->kitchenId)->get(),
        ]);
    }

    public function getItemsProperty()
    {
        return MenuItem::whereNull('kot_place_id')
            ->where('item_name', 'like', '%' . $this->search . '%')
            ->get();
    }
}
