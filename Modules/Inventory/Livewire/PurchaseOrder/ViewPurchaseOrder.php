<?php

namespace Modules\Inventory\Livewire\PurchaseOrder;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewPurchaseOrder extends Component
{
    public $showModal = false;
    public $purchaseOrder;
    public $activeTab = 'details'; // details, payments

    protected $listeners = [
        'viewPurchaseOrder' => 'show',
        'purchaseOrderPaymentSaved' => '$refresh',
    ];

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder->load([
            'supplier',
            'branch',
            'items.inventoryItem' => function($q) {
                $q->withoutGlobalScopes();
            },
            'items.inventoryItem.unit' => function($q) {
                $q->withoutGlobalScopes();
            },
            'payments.account',
            'payments.addedBy',
        ]);
        $this->activeTab = 'details';
        $this->showModal = true;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'payments') {
            $this->purchaseOrder->load(['payments.account', 'payments.addedBy']);
        }
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
