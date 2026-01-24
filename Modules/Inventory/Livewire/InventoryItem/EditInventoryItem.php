<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\Unit;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\Supplier;
use Illuminate\Validation\Rule;

class EditInventoryItem extends Component
{
    use LivewireAlert;
    
    public $inventoryItem;
    public $name;
    public $itemCategory;
    public $unit;
    public $thresholdQuantity = 0;
    public $preferredSupplier;
    public $itemCategories;
    public $units;
    public $suppliers;
    // Removed: reorder_quantity (auto-purchase disabled)
    public $unitPurchasePrice = 0;

    protected $listeners = [
        'preferredSupplier-selected' => 'onPreferredSupplierSelected'
    ];

    public function mount($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;
        $this->name = $inventoryItem->name;
        $this->itemCategory = $inventoryItem->inventory_item_category_id;
        $this->unit = $inventoryItem->unit_id;
        $this->thresholdQuantity = $inventoryItem->threshold_quantity;
        $this->preferredSupplier = $inventoryItem->preferred_supplier_id;

        $this->unitPurchasePrice = $inventoryItem->unit_purchase_price;
        $this->itemCategories = InventoryItemCategory::all();
        $this->units = Unit::all();
        $this->suppliers = Supplier::all();
    }

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_items', 'name')
                    ->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))
                    ->ignore($this->inventoryItem->id),
            ],
            'itemCategory' => 'required|exists:inventory_item_categories,id',
            'unit' => 'required|exists:units,id',
            'thresholdQuantity' => 'required|numeric|min:0',
            'preferredSupplier' => 'nullable|exists:suppliers,id',

            'unitPurchasePrice' => 'required|numeric|min:0',
        ];
    }

    protected function messages()
    {
        return [
            'name.unique' => 'An inventory item with this name already exists. Please use a different name.',
        ];
    }

    public function submitForm()
    {
        $this->validate();

        $this->inventoryItem->update([
            'name' => $this->name,
            'restaurant_id' => $this->inventoryItem->restaurant_id ?? restaurant()->id,
            'inventory_item_category_id' => $this->itemCategory,
            'unit_id' => $this->unit,
            'threshold_quantity' => $this->thresholdQuantity,
            'preferred_supplier_id' => $this->preferredSupplier,

            'unit_purchase_price' => $this->unitPurchasePrice,
        ]);

        $this->dispatch('hideEditInventoryItemModal');

        $this->alert('success', __('inventory::modules.inventoryItem.inventoryItemUpdated'));
    }

    public function onPreferredSupplierSelected($supplierId)
    {
        $this->preferredSupplier = $supplierId;
    }

    public function render()
    {
        return view('inventory::livewire.inventory-item.edit-inventory-item');
    }
}
