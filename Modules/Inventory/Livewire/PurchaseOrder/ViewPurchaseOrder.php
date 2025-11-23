<?php

namespace Modules\Inventory\Livewire\PurchaseOrder;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewPurchaseOrder extends Component
{
    public $showModal = false;
    public $purchaseOrder;

    protected $listeners = ['viewPurchaseOrder' => 'show'];

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder->load([
            'supplier',
            'branch', // Load branch relationship
            'items.inventoryItem' => function($q) {
                $q->withoutGlobalScopes();
            },
            'items.inventoryItem.unit' => function($q) {
                $q->withoutGlobalScopes();
            }
        ]);
        $this->showModal = true;
    }

    public function downloadPdf()
    {
        // Reload with withoutGlobalScopes just in case
        $this->purchaseOrder->load([
            'supplier',
            'branch',
            'items.inventoryItem' => function($q) {
                $q->withoutGlobalScopes();
            },
            'items.inventoryItem.unit' => function($q) {
                $q->withoutGlobalScopes();
            }
        ]);

        $pdf = PDF::loadView('inventory::pdfs.purchase-order', [
            'purchaseOrder' => $this->purchaseOrder
        ]);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, "PO-{$this->purchaseOrder->po_number}.pdf");
    }

    public function render()
    {
        return view('inventory::livewire.purchase-order.view-purchase-order');
    }
}
