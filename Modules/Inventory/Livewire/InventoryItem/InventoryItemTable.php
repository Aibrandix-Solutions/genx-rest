<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryItem;
use Livewire\Attributes\On;
class InventoryItemTable extends Component
{
    use WithPagination;

    public $search = '';
    public $showEditInventoryItemModal = false;
    public $showDeleteInventoryItemModal = false;
    public $inventoryItem;
    public $perPage = 20;

    public function mount($search, $perPage = 20)
    {
        $this->search = $search;
        $this->perPage = $perPage;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showEditInventoryItem($id)
    {
        $this->inventoryItem = InventoryItem::findOrFail($id);
        $this->showEditInventoryItemModal = true;
    }

    public function showDeleteInventoryItem($id)
    {
        $this->inventoryItem = InventoryItem::findOrFail($id);
        $this->showDeleteInventoryItemModal = true;
    }

    public function deleteInventoryItem($id)
    {
        $inventoryItem = InventoryItem::destroy($id);
        $this->showDeleteInventoryItemModal = false;
        $this->inventoryItem = null;
    }

    #[On('hideDeleteInventoryItemModal')]
    public function hideDeleteInventoryItemModal()
    {
        $this->showDeleteInventoryItemModal = false;
    }

    public function render()
    {
        $inventoryItems = InventoryItem::with(['category', 'unit', 'supplier'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('inventory::livewire.inventory-item.inventory-item-table', [
            'inventoryItems' => $inventoryItems
        ]);
    }
} 