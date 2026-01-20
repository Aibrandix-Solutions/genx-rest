<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;

class ItemModifiers extends Component
{
    public $selectedModifierItem;
    public $menuItemId;
    public $selectedModifiers = [];
    public $finalModifiers = [];
    public $modifiers = [];
    public $requiredModifiers = [];
    public $selectedVariationName;
    public $orderTypeId;
    public $deliveryAppId;

    public function mount()
    {
        $variationId = null;

        if (strpos($this->menuItemId, '_') !== false) {
            [$itemId, $variationId] = explode('_', $this->menuItemId);
            $this->menuItemId = $itemId;
            $menuItemVariation = MenuItemVariation::find($variationId);
            $this->selectedVariationName = $menuItemVariation->variation ?? null;
        }

        $this->selectedModifierItem = MenuItem::with(['modifierGroups', 'modifierGroups.options'])
            ->findOrFail($this->menuItemId);

        // New logic for modifiers
        // Get all modifiers that apply to this item (base modifiers where variation_id is null)
        $baseModifiers = \App\Models\ModifierGroup::whereHas('itemModifiers', function($query) {
            $query->where('menu_item_id', $this->menuItemId)
                ->whereNull('menu_item_variation_id');
        })->with(['options', 'itemModifiers' => function($query) {
            $query->where('menu_item_id', $this->menuItemId)
                ->whereNull('menu_item_variation_id');
        }])->get();

        $this->modifiers = $baseModifiers;

        // If we have a variation, add variation-specific modifiers
        if ($variationId) {
            $variationSpecificModifiers = \App\Models\ModifierGroup::whereHas('itemModifiers', function($query) use ($variationId) {
                $query->where('menu_item_id', $this->menuItemId)
                    ->where('menu_item_variation_id', $variationId);
            })->with(['options', 'itemModifiers' => function($query) use ($variationId) {
                $query->where('menu_item_id', $this->menuItemId)
                    ->where('menu_item_variation_id', $variationId);
            }])->get();

            foreach ($variationSpecificModifiers as $modifier) {
                // Mark this modifier as variation-specific
                $modifier->variationSpecific = true;
                $modifier->menu_item_variation_id = $variationId;
            }

            // Merge variation-specific modifiers with base modifiers
            $this->modifiers = collect($baseModifiers)->concat($variationSpecificModifiers);
        }

        // Set price context on all modifier options
        if ($this->orderTypeId) {
            // Normalize delivery app ID to ensure it's either an integer or null
            $deliveryAppId = null;
            if ($this->deliveryAppId && $this->deliveryAppId !== 'default') {
                $deliveryAppId = is_numeric($this->deliveryAppId) ? (int)$this->deliveryAppId : null;
            }
            
            foreach ($this->modifiers as $modifierGroup) {
                foreach ($modifierGroup->options as $option) {
                    $option->setPriceContext($this->orderTypeId, $deliveryAppId);
                }
            }
        }

    }

    public function toggleSelection($groupId, $optionId)
    {
        // Backwards compatibility: legacy checkbox handler. Treat a "toggle" as qty 1/0.
        $current = (int) ($this->selectedModifiers[$optionId] ?? 0);
        if ($current > 0) {
            $this->setOptionQty($groupId, $optionId, 0);
            return;
        }

        $this->setOptionQty($groupId, $optionId, 1);
    }

    public function incrementOption(int $groupId, int $optionId): void
    {
        $current = (int) ($this->selectedModifiers[$optionId] ?? 0);
        $this->setOptionQty($groupId, $optionId, $current + 1);
    }

    public function decrementOption(int $groupId, int $optionId): void
    {
        $current = (int) ($this->selectedModifiers[$optionId] ?? 0);
        $this->setOptionQty($groupId, $optionId, max(0, $current - 1));
    }

    public function setOptionQty(int $groupId, int $optionId, int $qty): void
    {
        $modifierGroup = $this->modifiers instanceof \Illuminate\Support\Collection
            ? $this->modifiers->firstWhere('id', $groupId)
            : collect($this->modifiers)->firstWhere('id', $groupId);
        $allowMultiple = (bool) ($modifierGroup?->itemModifiers?->first()?->allow_multiple_selection ?? false);

        if (!$allowMultiple && $qty > 0) {
            foreach ($modifierGroup->options as $option) {
                if ((int) $option->id !== (int) $optionId) {
                    $this->selectedModifiers[(int) $option->id] = 0;
                }
            }
        }

        $this->selectedModifiers[(int) $optionId] = max(0, $qty);
    }

    public function saveModifiers()
    {
        $this->validateRequiredModifiers();

        $selected = collect($this->selectedModifiers)
            ->map(fn($qty) => (int) $qty)
            ->filter(fn($qty) => $qty > 0)
            ->toArray();

        $this->finalModifiers = [
            $this->menuItemId => $selected,
        ];

        $this->dispatch('setPosModifier', $this->finalModifiers);
    }

    public function validateRequiredModifiers()
    {
        $rules = [];
        $messages = [];

        // Use the already loaded modifiers instead of querying the database again
        foreach ($this->modifiers as $modifierGroup) {

            $isRequired = $modifierGroup->itemModifiers->isNotEmpty()
                ? ($modifierGroup->itemModifiers->first()->is_required ?? false)
                : false;

            if ($isRequired) {
                $hasSelection = false;
                foreach ($modifierGroup->options as $option) {
                    $qty = (int) ($this->selectedModifiers[(int) $option->id] ?? 0);
                    if ($qty > 0) {
                        $hasSelection = true;
                        break;
                    }
                }

                if (!$hasSelection) {
                    $rules["requiredModifiers.{$modifierGroup->id}"] = 'required';
                    $messages["requiredModifiers.{$modifierGroup->id}.required"] = __('validation.requiredModifierGroup', ['name' => $modifierGroup->name]);
                }
            }
        }

        if (!empty($rules)) {
            $this->validate($rules, $messages);
        }
    }

    public function render()
    {
        return view('livewire.pos.item-modifiers');
    }
}
