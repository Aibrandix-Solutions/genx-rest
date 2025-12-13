<?php

namespace Modules\Inventory\Livewire\InventoryMovement;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryMovement;

class ViewMovement extends Component
{
    public $movement;
    public $showEditModal = false;
    public $selectedMovement = null;

    protected $listeners = [
        'showEditMovementModal' => 'showEditModal',
        'movementUpdated' => 'handleMovementUpdated'
    ];

    public function mount(InventoryMovement $movement)
    {
        // Eager load all necessary relationships, including nested relationships
        $this->movement = InventoryMovement::with([
            'item.unit',
            'item.category',
            'addedBy',
            'sourceBranch',
            'transferBranch',
            'supplier'
        ])->findOrFail($movement->id);
    }

    public function handleMovementUpdated()
    {
        $this->showEditModal = false;
        $this->movement->refresh();
    }

    public function render()
    {
        return view('inventory::livewire.inventory-movement.view-movement');
    }
} 