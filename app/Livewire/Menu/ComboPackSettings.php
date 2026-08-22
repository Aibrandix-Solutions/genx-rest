<?php

namespace App\Livewire\Menu;

use App\Models\ComboPack;
use App\Models\ComboPackItem;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use App\Helper\Files;
use Livewire\Component;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class ComboPackSettings extends Component
{
    use WithFileUploads, LivewireAlert;

    public $comboPacks = [];
    public $showComboForm = false;
    public $editingComboId = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $imageTemp = null;
    public $image = null;
    public $discountType = 'fixed';
    public $discountValue = '';
    public $isActive = true;
    public $sortOrder = null;
    
    // Items management
    public $selectedItems = [];
    public $availableMenuItems = [];
    public $itemQuantities = [];
    public $itemVariations = [];
    
    // Calculated prices
    public $regularPrice = 0;
    public $discountedPrice = 0;
    public $discountAmount = 0;
    public $discountPercent = 0;

    protected function rules()
    {
        // Discount value bounds: percent must be 0-100, fixed must not exceed regular price
        if ($this->discountType === 'percent') {
            $discountValueRule = 'required|numeric|min:0|max:100';
        } elseif ($this->regularPrice > 0) {
            $discountValueRule = 'required|numeric|min:0|max:' . $this->regularPrice;
        } else {
            $discountValueRule = 'required|numeric|min:0';
        }

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'imageTemp' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'discountType' => 'required|in:fixed,percent',
            'discountValue' => $discountValueRule,
            'isActive' => 'boolean',
            'selectedItems' => 'required|array|min:1',
            'selectedItems.*' => 'exists:menu_items,id',
            'itemQuantities.*' => 'required|integer|min:1',
        ];
    }

    public function mount()
    {
        $this->loadComboPacks();
        $this->loadMenuItems();
    }

    public function loadComboPacks()
    {
        $branch = branch();
        if (!$branch) {
            $this->comboPacks = collect([]);
            return;
        }
        
        // Load all combo packs for the branch, ordered by sort_order then id
        $this->comboPacks = ComboPack::where('branch_id', $branch->id)
            ->with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function loadMenuItems()
    {
        $branch = branch();
        if (!$branch) {
            $this->availableMenuItems = collect([]);
            return;
        }
        
        $this->availableMenuItems = MenuItem::where('branch_id', $branch->id)
            ->with(['variations'])
            ->get();
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->showComboForm = true;
    }

    public function openEditForm($comboId)
    {
        $combo = ComboPack::with(['comboPackItems.menuItem', 'comboPackItems.menuItemVariation'])
            ->findOrFail($comboId);

        $this->editingComboId = $comboId;
        $this->name = $combo->getTranslation('name', app()->getLocale());
        $this->description = $combo->getTranslation('description', app()->getLocale()) ?? '';
        $this->image = $combo->image;
        $this->discountType = $combo->discount_type;
        $this->discountValue = $combo->discount_type === 'fixed' 
            ? $combo->discount_amount 
            : $combo->discount_percent;
        $this->isActive = $combo->is_active;
        $this->sortOrder = $combo->sort_order;
        $this->regularPrice = $combo->regular_price;
        $this->discountedPrice = $combo->discounted_price;
        $this->discountAmount = $combo->discount_amount;
        $this->discountPercent = $combo->discount_percent;

        // Load selected items
        $this->selectedItems = [];
        $this->itemQuantities = [];
        $this->itemVariations = [];
        
        foreach ($combo->comboPackItems as $comboItem) {
            $key = $comboItem->menu_item_id . '_' . ($comboItem->menu_item_variation_id ?? '0');
            $this->selectedItems[] = $key;
            $this->itemQuantities[$key] = $comboItem->quantity;
            $this->itemVariations[$key] = $comboItem->menu_item_variation_id;
        }

        $this->showComboForm = true;
    }

    public function resetForm()
    {
        $this->editingComboId = null;
        $this->name = '';
        $this->description = '';
        $this->imageTemp = null;
        $this->image = null;
        $this->discountType = 'fixed';
        $this->discountValue = '';
        $this->isActive = true;
        $this->sortOrder = null;
        $this->selectedItems = [];
        $this->itemQuantities = [];
        $this->itemVariations = [];
        $this->regularPrice = 0;
        $this->discountedPrice = 0;
        $this->discountAmount = 0;
        $this->discountPercent = 0;
    }

    public function addItem($itemKey)
    {
        // itemKey can be either "menuItemId" or "menuItemId_variationId"
        $key = $itemKey;
        if (strpos($itemKey, '_') === false) {
            // If no underscore, it's just a menu item ID, add _0
            $key = $itemKey . '_0';
        }
        
        if (!in_array($key, $this->selectedItems)) {
            $this->selectedItems[] = $key;
            $this->itemQuantities[$key] = 1;
            [$menuItemId, $variationId] = explode('_', $key);
            $this->itemVariations[$key] = $variationId !== '0' ? (int)$variationId : null;
        }
        $this->calculatePrices();
    }

    public function removeItem($key)
    {
        $index = array_search($key, $this->selectedItems);
        if ($index !== false) {
            unset($this->selectedItems[$index]);
            $this->selectedItems = array_values($this->selectedItems);
            unset($this->itemQuantities[$key]);
            unset($this->itemVariations[$key]);
        }
        $this->calculatePrices();
    }

    public function updatedSelectedItems()
    {
        $this->calculatePrices();
    }

    public function updatedDiscountType()
    {
        $this->calculatePrices();
    }

    public function updatedDiscountValue()
    {
        $this->calculatePrices();
    }

    public function updatedItemQuantities()
    {
        $this->calculatePrices();
    }

    public function calculatePrices()
    {
        $this->regularPrice = 0;

        foreach ($this->selectedItems as $key) {
            [$menuItemId, $variationId] = explode('_', $key);
            $menuItem = MenuItem::find($menuItemId);
            
            if ($menuItem) {
                $rawQuantity = $this->itemQuantities[$key] ?? 1;
                if (is_string($rawQuantity)) {
                    $rawQuantity = str_replace(',', '', $rawQuantity);
                }
                $quantity = is_numeric($rawQuantity) ? (float)$rawQuantity : 1.0;
                $variationId = $variationId !== '0' ? (int)$variationId : null;
                
                if ($variationId) {
                    $variation = MenuItemVariation::find($variationId);
                    $price = $variation ? (float)$variation->price : (float)$menuItem->price;
                } else {
                    $price = (float)$menuItem->price;
                }
                
                $this->regularPrice += $price * $quantity;
            }
        }

        $this->regularPrice = round($this->regularPrice, 2);

        // Calculate discount
        if ($this->discountType === 'fixed' && $this->discountValue) {
            $this->discountAmount = min((float)$this->discountValue, $this->regularPrice);
            $this->discountedPrice = $this->regularPrice - $this->discountAmount;
            if ($this->regularPrice > 0) {
                $this->discountPercent = round(($this->discountAmount / $this->regularPrice) * 100, 2);
            }
        } elseif ($this->discountType === 'percent' && $this->discountValue) {
            $this->discountPercent = min((float)$this->discountValue, 100);
            $this->discountAmount = round(($this->regularPrice * $this->discountPercent) / 100, 2);
            $this->discountedPrice = $this->regularPrice - $this->discountAmount;
        } else {
            $this->discountedPrice = $this->regularPrice;
            $this->discountAmount = 0;
            $this->discountPercent = 0;
        }
    }

    public function saveCombo()
    {
        // Validate selected items
        if (empty($this->selectedItems)) {
            $this->alert('error', __('modules.combo.selectAtLeastOneItem'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'imageTemp' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'discountType' => 'required|in:fixed,percent',
            'discountValue' => 'required|numeric|min:0',
            'isActive' => 'boolean',
            'selectedItems' => 'required|array|min:1',
        ]);

        try {
            DB::transaction(function () {
                // Handle image upload
                if ($this->imageTemp) {
                    if ($this->image) {
                        Files::deleteFile($this->image, 'combo_packs');
                    }
                    $this->image = Files::uploadLocalOrS3($this->imageTemp, 'combo_packs');
                }

                // Calculate final prices
                $this->calculatePrices();

                // Create or update combo pack
                $branch = branch();
                if (!$branch) {
                    throw new \Exception('No branch selected');
                }
                
                // Prepare translatable fields
                $nameTranslations = [];
                $descriptionTranslations = [];
                $currentLocale = app()->getLocale();
                
                // If editing, preserve existing translations
                if ($this->editingComboId) {
                    $existingCombo = ComboPack::find($this->editingComboId);
                    if ($existingCombo) {
                        $nameTranslations = $existingCombo->getTranslations('name');
                        $descriptionTranslations = $existingCombo->getTranslations('description');
                    }
                }
                
                // Set current locale translation
                $nameTranslations[$currentLocale] = $this->name;
                if ($this->description) {
                    $descriptionTranslations[$currentLocale] = $this->description;
                }
                
                $combo = ComboPack::updateOrCreate(
                    ['id' => $this->editingComboId],
                    [
                        'branch_id' => $branch->id,
                        'name' => $nameTranslations,
                        'description' => $descriptionTranslations,
                        'image' => $this->image,
                        'regular_price' => $this->regularPrice,
                        'discounted_price' => $this->discountedPrice,
                        'discount_amount' => $this->discountAmount,
                        'discount_percent' => $this->discountPercent,
                        'discount_type' => $this->discountType,
                        'is_active' => $this->isActive,
                        'sort_order' => $this->sortOrder,
                    ]
                );

                // Delete existing combo items
                $combo->comboPackItems()->delete();

                // Create new combo items with variation ownership validation
                $sortOrder = 0;
                foreach ($this->selectedItems as $key) {
                    [$menuItemId, $variationId] = explode('_', $key);
                    $variationId = $variationId !== '0' ? (int)$variationId : null;
                    $quantity = (int)($this->itemQuantities[$key] ?? 1);

                    // Phase 1.3: Validate that variation belongs to its menu item
                    if ($variationId) {
                        $validVariation = \App\Models\MenuItemVariation::where('id', $variationId)
                            ->where('menu_item_id', (int)$menuItemId)
                            ->exists();
                        if (!$validVariation) {
                            throw new \Exception("Variation ID {$variationId} does not belong to menu item ID {$menuItemId}.");
                        }
                    }

                    ComboPackItem::create([
                        'combo_pack_id' => $combo->id,
                        'menu_item_id' => (int)$menuItemId,
                        'menu_item_variation_id' => $variationId,
                        'quantity' => $quantity,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            });

            $this->alert('success', __('modules.combo.comboPackSaved'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            $this->loadComboPacks();
            $this->resetForm();
            $this->showComboForm = false;

        } catch (\Exception $e) {
            $this->alert('error', __('modules.combo.errorSavingComboPack') . ': ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function deleteCombo($comboId)
    {
        try {
            $combo = ComboPack::findOrFail($comboId);
            
            if ($combo->image) {
                Files::deleteFile($combo->image, 'combo_packs');
            }
            
            $combo->delete();

            $this->alert('success', __('modules.combo.comboPackDeleted'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            $this->loadComboPacks();
        } catch (\Exception $e) {
            $this->alert('error', __('modules.combo.errorDeletingComboPack') . ': ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function toggleActive($comboId)
    {
        try {
            $combo = ComboPack::findOrFail($comboId);
            $combo->update(['is_active' => !$combo->is_active]);
            
            $this->loadComboPacks();
            
            $this->alert('success', __('modules.combo.comboPackUpdated'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        } catch (\Exception $e) {
            $this->alert('error', __('modules.combo.errorUpdatingComboPack') . ': ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function cancelForm()
    {
        $this->resetForm();
        $this->showComboForm = false;
    }

    public function updateSortOrder($comboIds)
    {
        try {
            foreach ($comboIds as $index => $comboId) {
                ComboPack::where('id', $comboId)
                    ->update(['sort_order' => $index + 1]);
            }
            
            $this->loadComboPacks();
            
            $this->alert('success', __('modules.combo.comboPackUpdated'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        } catch (\Exception $e) {
            $this->alert('error', __('modules.combo.errorUpdatingComboPack') . ': ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function render()
    {
        // Reload combo packs to ensure we have the latest data
        $this->loadComboPacks();
        
        return view('livewire.menu.combo-pack-settings');
    }
}

