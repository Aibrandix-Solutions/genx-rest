<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Livewire\Component;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\InventoryItemExport;

class InventoryItemList extends Component
{
    public $search = '';
    public $showAddInventoryItem = false;
    public $showEditInventoryItemModal = false;
    public $perPage = 20;

    #[On('hideAddInventoryItem')]
    public function hideAddInventoryItem()
    {
        $this->showAddInventoryItem = false;
    }

    #[On('hideEditInventoryItemModal')]
    public function hideEditInventoryItemModal()
    {
        $this->showEditInventoryItemModal = false;
    }

    public function export()
    {
        return Excel::download(new InventoryItemExport, 'inventory-items.xlsx');
    }

    public function render()
    {
        return view('inventory::livewire.inventory-item.inventory-item-list');
    }
}
