<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Illuminate\Database\QueryException;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\Unit;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\Supplier;
use Illuminate\Validation\Rule;

class AddInventoryItem extends Component
{
    use LivewireAlert;
    
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

    protected $listeners = [
        'preferredSupplier-selected' => 'onPreferredSupplierSelected'
    ];

    public function mount()
    {
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
                    ->where(fn ($q) => $q->where('restaurant_id', restaurant()->id)),
            ],
            'itemCode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('inventory_items', 'item_code')
                    ->where(fn ($q) => $q->where('restaurant_id', restaurant()->id)),
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
            'itemCode.unique' => 'This item code is already in use. Please choose a different one.',
        ];
    }

    public function submitForm()
    {
        $this->validate();

        $userSuppliedCode = trim((string) $this->itemCode) !== '';
        $restaurantId = (int) restaurant()->id;

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
        ];

        $maxAttempts = 15;
        $created = null;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $created = InventoryItem::create($payload);
                break;
            } catch (QueryException $e) {
                if ($userSuppliedCode || !InventoryItem::isDuplicateRestaurantItemCodeException($e)) {
                    throw $e;
                }
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                // Race: another row took the candidate code; pick the next one.
                $payload['item_code'] = InventoryItem::generateNextItemCodeForRestaurant($restaurantId);
                $this->itemCode = $payload['item_code'];
            }
        }

        $this->dispatch('inventoryItemAdded');
        $this->reset(['name', 'itemCode', 'itemCategory', 'unit', 'thresholdQuantity', 'preferredSupplier', 'unitPurchasePrice']);
        $this->showAddInventoryItem = false;

        $this->alert('success', __('inventory::modules.inventoryItem.inventoryItemAdded'));
    }

    public function clearSelection()
    {
        $this->preferredSupplier = null;
    }


    public function onPreferredSupplierSelected($supplierId)
    {
        $this->preferredSupplier = $supplierId;
    }

    public function render()
    {
        return view('inventory::livewire.inventory-item.add-inventory-item');
    }
}
