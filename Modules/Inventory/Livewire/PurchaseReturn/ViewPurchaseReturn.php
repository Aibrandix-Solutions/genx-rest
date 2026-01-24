<?php

namespace Modules\Inventory\Livewire\PurchaseReturn;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseReturn;

class ViewPurchaseReturn extends Component
{
    public $showModal = false;
    public $purchaseReturn;

    public $activeTab = 'details';

    protected $listeners = [
        'viewPurchaseReturn' => 'show',
        'purchaseReturnPaymentSaved' => '$refresh',
    ];

    public function show(...$args)
    {
        // Extract ID from event data - can be passed as object or directly
        $id = null;
        if (!empty($args)) {
            $firstArg = $args[0];
            if (is_array($firstArg)) {
                $id = $firstArg['purchaseReturn'] ?? $firstArg['id'] ?? null;
            } elseif (is_numeric($firstArg)) {
                $id = $firstArg;
            } elseif (is_object($firstArg)) {
                $id = $firstArg->purchaseReturn ?? $firstArg->id ?? null;
            }
        }
        
        if (!$id) {
            return;
        }

        $this->purchaseReturn = PurchaseReturn::with([
            'supplier',
            'purchaseOrder',
            'branch',
            'addedBy',
            'payments.account',
            'payments.addedBy',
            'items.inventoryItem.unit',
        ])->find($id);
        
        if (!$this->purchaseReturn) {
            return;
        }
        
        $this->activeTab = 'details';
        $this->showModal = true;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'payments') {
            $this->purchaseReturn->load(['payments.account', 'payments.addedBy']);
        }
    }

    public function render()
    {
        return view('inventory::livewire.purchase-return.view-purchase-return');
    }
}

