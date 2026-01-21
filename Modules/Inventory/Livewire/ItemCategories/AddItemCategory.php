<?php

namespace Modules\Inventory\Livewire\ItemCategories;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItemCategory;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class AddItemCategory extends Component
{
    use LivewireAlert;
    public $itemCategoryName;

    public function submitForm()
    {
        $this->validate([
            'itemCategoryName' => 'required|string|max:255|unique:inventory_item_categories,name,null,id,restaurant_id,' . restaurant()->id,
        ]);

        $itemCategory = InventoryItemCategory::create([
            'name' => $this->itemCategoryName,
            'restaurant_id' => restaurant()->id,
        ]);

        $this->itemCategoryName = '';
        $this->alert('success', __('inventory::modules.itemCategory.itemCategoryAdded'));
        $this->dispatch('hideAddItemCategory');
    }

    public function render()
    {
        return view('inventory::livewire.item-categories.add-item-category');
    }
}
