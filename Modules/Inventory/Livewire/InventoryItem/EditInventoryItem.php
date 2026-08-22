<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Illuminate\Database\QueryException;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\Unit;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\Supplier;
use Illuminate\Validation\Rule;

class EditInventoryItem extends Component
{
    use LivewireAlert;
    
    public $inventoryItem;
    public $name;
    public $itemCode;
    public $itemCategory;
    public $unit;
    public $thresholdQuantity = 0;
    public $preferredSupplier;
    public $itemCategories;
    public $units;
    public $suppliers;
    // Removed: reorder_quantity (auto-purchase disabled)
    public $unitPurchasePrice = 0;
    public $isActive = true;

    protected $listeners = [
        'preferredSupplier-selected' => 'onPreferredSupplierSelected'
    ];

    public function mount($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;
        $this->name = $inventoryItem->name;
        $this->itemCode = $inventoryItem->item_code;
        $this->itemCategory = $inventoryItem->inventory_item_category_id;
        $this->unit = $inventoryItem->unit_id;
        $this->thresholdQuantity = $inventoryItem->threshold_quantity;
        $this->preferredSupplier = $inventoryItem->preferred_supplier_id;

        $this->unitPurchasePrice = $inventoryItem->unit_purchase_price;
        $this->isActive = (bool) ($inventoryItem->is_active ?? true);
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
            'itemCode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('inventory_items', 'item_code')
                    ->where(fn ($q) => $q->where('restaurant_id', restaurant()->id))
                    ->ignore($this->inventoryItem->id),
            ],
            'itemCategory' => 'required|exists:inventory_item_categories,id',
            'unit' => 'required|exists:units,id',
            'thresholdQuantity' => 'required|numeric|min:0',
            'preferredSupplier' => 'nullable|exists:suppliers,id',

            'unitPurchasePrice' => 'required|numeric|min:0',
            'isActive' => 'boolean',
        ];
    }

    protected function messages()
    {
        return [
            'name.unique' => 'An inventory item with this name already exists. Please use a different name.',
            'itemCode.unique' => 'This item code is already in use. Please choose a different one.',
        ];
    }

    public function submitForm()
    {
        $this->validate();

        $restaurantId = (int) ($this->inventoryItem->restaurant_id ?? restaurant()->id);
        $userSuppliedCode = trim((string) $this->itemCode) !== '';

        if (!$userSuppliedCode) {
            $this->itemCode = InventoryItem::generateNextItemCodeForRestaurant($restaurantId);
        } else {
            $this->itemCode = trim((string) $this->itemCode);
        }

        $payload = [
            'name' => $this->name,
            'restaurant_id' => $restaurantId,
            'item_code' => $this->itemCode,
            'inventory_item_category_id' => $this->itemCategory,
            'unit_id' => $this->unit,
            'threshold_quantity' => $this->thresholdQuantity,
            'preferred_supplier_id' => $this->preferredSupplier,
            'unit_purchase_price' => $this->unitPurchasePrice,
            'is_active' => (bool) $this->isActive,
        ];

        $maxAttempts = 15;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->inventoryItem->update($payload);
                break;
            } catch (QueryException $e) {
                if ($userSuppliedCode || !InventoryItem::isDuplicateRestaurantItemCodeException($e)) {
                    throw $e;
                }
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                $payload['item_code'] = InventoryItem::generateNextItemCodeForRestaurant($restaurantId);
                $this->itemCode = $payload['item_code'];
            }
        }

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
