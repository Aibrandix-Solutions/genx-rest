<?php

namespace Modules\Kitchen\Livewire;

use App\Models\KotPlace;
use App\Models\MenuItem;
use Livewire\Component;
use Modules\Kitchen\Entities\Kitchen;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class AllKitchens extends Component
{
    use LivewireAlert, WithPagination;
    public $name;
    public $showEditKitchenModal = false;
    public $selectedKitchen;
    public $confirmDeleteKitchenPlacesModal = false;
    public $deleteKitchenPlaces;
    public $deleteExpense;
    public $showAddItemModal = false;
    public $selectedKitchenId;
    public $showKitchenStatusModal = false;
    public $selectedItems = [];
    public $showAddkitchenPlaces = false;
    public $showAssignItemModal = false;
    public $itemToAssign = null;
    public $selectedKitchenForAssignment = null;
    public $searchItem = '';
    public $searchResults;

    public function mount()
    {
        $this->searchResults = collect();
    }

    public function updatedSearchItem()
    {
        if (empty($this->searchItem)) {
            $this->searchResults = collect();
            return;
        }

        $this->searchResults = MenuItem::with(['variations', 'kotPlace'])
            ->where('item_name', 'like', '%' . $this->searchItem . '%')
            ->get(); // Removed the whereNotNull('kot_place_id') to include unassigned items
    }

    public function clearSearch()
    {
        $this->searchItem = '';
        $this->searchResults = collect();
    }

    public function showEditKitchen($kitchenId)
    {
        $this->selectedKitchen = KotPlace::find($kitchenId);
        $this->showEditKitchenModal = true;
    }

    #[On('hideEditKitchen')]
    public function hideEditKitchen()
    {
        $this->showEditKitchenModal = false;
    }

    public function confirmDeleteKitchenPlaces($id)
    {
        $this->deleteKitchenPlaces = $id;
        $this->confirmDeleteKitchenPlacesModal = true;
    }

    public function deleteKitchenPlace()
    {
        $kitchen = KotPlace::find($this->deleteKitchenPlaces);

        if ($kitchen && $kitchen->is_default) {
            $this->alert('error', __('kitchen::messages.cannotDeleteDefaultKitchen'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        } else {
            $kitchen?->delete();

            $this->alert('success', __('kitchen::messages.KitchenPlacesDeleted'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }

        $this->confirmDeleteKitchenPlacesModal = false;
        $this->deleteKitchenPlaces = null;
    }

    public function showKitchenStatusPopup($kitchenId)
    {
        $this->selectedKitchenId = $kitchenId;
        $this->showKitchenStatusModal = true;
    }

    #[On('hideKitchenStatusPopup')]
    public function hideshowKitchenStatusPopup()
    {
        $this->showKitchenStatusModal = false;
    }

    public function toggleKitchenStatus()
    {
        $kitchen = KotPlace::with('menuItems')->find($this->selectedKitchenId);

        if ($kitchen->is_default) {
            $this->alert('error', __('kitchen::messages.cannotInactiveDefaultKitchen'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            $this->dispatch('hideKitchenStatusPopup');
            return;
        }

        if ($kitchen->is_active == true) {
            $defaultKitchen = KotPlace::where('is_default', true)->first();

            if ($kitchen && $defaultKitchen && $kitchen->id !== $defaultKitchen->id) {
                // Move items from this kitchen to default kitchen (both legacy and pivot)
                $kitchen->menuItems()->update(['kot_place_id' => $defaultKitchen->id]);

                // Update pivot: detach from deactivated kitchen, attach to default
                $itemIds = $kitchen->menuItemsMany()->pluck('menu_items.id')->toArray();
                $kitchen->menuItemsMany()->detach($itemIds);
                foreach ($itemIds as $itemId) {
                    $exists = $defaultKitchen->menuItemsMany()->where('menu_items.id', $itemId)->exists();
                    if (!$exists) {
                        $defaultKitchen->menuItemsMany()->attach($itemId, ['is_primary' => false]);
                    }
                }
            }
        }

        $kitchen->is_active = !$kitchen->is_active;
        $kitchen->save();

        $this->dispatch('hideKitchenStatusPopup');

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function addItemToKitchen($kitchenId)
    {
        $this->selectedKitchenId = $kitchenId;
        $this->showAddItemModal = true;
    }

    #[On('hideItemToKitchen')]
    public function hideItemToKitchen()
    {

        $this->showAddItemModal = false;
    }

    public function removeItemFromKitchen($itemId, $kitchenId = null)
    {
        $item = MenuItem::findOrFail($itemId);

        if ($kitchenId) {
            // Remove from specific kitchen (pivot table)
            $item->kotPlaces()->detach($kitchenId);

            // If this was the legacy kot_place_id, reassign to next available
            if ($item->kot_place_id == $kitchenId) {
                $nextKitchen = $item->kotPlaces()->first();
                $item->kot_place_id = $nextKitchen?->id;
                $item->save();
            }
        } else {
            // Legacy behavior: remove from all kitchens
            $item->kotPlaces()->detach();
            $item->kot_place_id = null;
            $item->save();
        }

        $this->alert('success', __('kitchen::messages.itemRemovedFromKitchen'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function assignItemToKitchen($itemId)
    {
        $this->itemToAssign = MenuItem::findOrFail($itemId);
        $this->showAssignItemModal = true;
    }

    public function confirmAssignItem()
    {
        if ($this->selectedKitchenForAssignment && $this->itemToAssign) {
            // Set legacy kot_place_id if not already set
            if (!$this->itemToAssign->kot_place_id) {
                $this->itemToAssign->kot_place_id = $this->selectedKitchenForAssignment;
                $this->itemToAssign->save();
            }

            // Sync pivot table (add without removing others)
            $existingIds = $this->itemToAssign->kotPlaces()->pluck('kot_places.id')->toArray();
            if (!in_array($this->selectedKitchenForAssignment, $existingIds)) {
                $this->itemToAssign->kotPlaces()->attach($this->selectedKitchenForAssignment, [
                    'is_primary' => empty($existingIds),
                ]);
            }

            $this->alert('success', __('kitchen::messages.itemAssignedToKitchen'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            $this->showAssignItemModal = false;
            $this->itemToAssign = null;
            $this->selectedKitchenForAssignment = null;
        }
    }

    public function render()
    {
        $kitchens = KotPlace::with(['printerSetting', 'menuItems.variations', 'menuItems.kotPlaces'])
            ->paginate(10);

        // Get all kitchens for the assign modal (not paginated)
        $allKitchens = KotPlace::where('is_active', true)->get();

        // Get missing items (items not assigned to any kitchen via either legacy or pivot)
        $missingItems = MenuItem::with('variations')
            ->whereNull('kot_place_id')
            ->whereDoesntHave('kotPlaces')
            ->get();

        return view('kitchen::livewire.all-kitchens', [
            'kitchens' => $kitchens,
            'allKitchens' => $allKitchens,
            'missingItems' => $missingItems,
        ]);
    }
}
