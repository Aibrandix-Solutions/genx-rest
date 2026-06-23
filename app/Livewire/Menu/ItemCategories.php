<?php

namespace App\Livewire\Menu;

use App\Models\ItemCategory;
use App\Models\MenuItem;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class ItemCategories extends Component
{

    use WithPagination, WithoutUrlPagination, LivewireAlert;

    public $showMenuCategoryModal = false;
    public $showEditItemCategory = false;
    public $itemCategory;
    public $confirmDeleteCategory = false;
    public $search;

    // Items preview panel — data loaded at render time, shown via Alpine (no round-trip)
    // No Livewire properties needed for the modal itself

    public function showEditCategory($id)
    {
        $this->showEditItemCategory = true;
        $this->itemCategory = ItemCategory::findOrFail($id);
    }

    public function showDeleteCategory($id)
    {
        $this->confirmDeleteCategory = true;
        $this->itemCategory = ItemCategory::findOrFail($id);
    }

    public function deleteItemCategory($id)
    {
        ItemCategory::destroy($id);
        $this->confirmDeleteCategory = false;

        $this->itemCategory = null;

        $this->alert('success', __('messages.menuItemCategoryDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    #[On('hideCategoryModal')]
    public function hideCategoryModal()
    {
        $this->showMenuCategoryModal = false;
        $this->showEditItemCategory = false;
    }

    public function render()
    {
        $branchId = branch()->id;
        $currencyId = restaurant()->currency_id;

        $categories = ItemCategory::withCount('items')
            ->search('category_name', $this->search)
            ->paginate(10);

        // Eager-load menu items for each category so Alpine can show them instantly
        $categoryIds = $categories->pluck('id');
        $itemsByCategory = MenuItem::withoutGlobalScopes()
            ->whereIn('item_category_id', $categoryIds)
            ->where('branch_id', $branchId)
            ->withCount('variations')
            ->with(['translations'])
            ->get()
            ->groupBy('item_category_id')
            ->map(fn($items) => $items->map(fn($item) => [
                'name'           => $item->item_name,
                'item_code'      => $item->item_code ?? '',
                'raw_price'      => (float) ($item->price ?? 0),
                'price'          => currency_format($item->price ?? 0, $currencyId),
                'has_variations' => $item->variations_count > 0,
            ])->values());

        // Attach preview data to each category as a dynamic property
        foreach ($categories as $category) {
            $category->menuItemsForPreview = $itemsByCategory->get($category->id, collect());
        }

        return view('livewire.menu.item-categories', compact('categories'));
    }

}
