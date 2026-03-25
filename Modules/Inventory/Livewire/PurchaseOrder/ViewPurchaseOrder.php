<?php

namespace Modules\Inventory\Livewire\PurchaseOrder;

use Livewire\Component;
use Modules\Inventory\Entities\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewPurchaseOrder extends Component
{
    public $showModal = false;
    public $purchaseOrder;
    public $activeTab = 'details'; // details, payments, attachments

    protected $listeners = [
        'viewPurchaseOrder' => 'show',
        'purchaseOrderPaymentSaved' => '$refresh',
    ];

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder->load([
            'supplier',
            'location.branch',
            'items.inventoryItem.unit',
            'payments.account',
            'payments.addedBy',
            'attachments',
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
        if ($tab === 'attachments') {
            $this->purchaseOrder->load(['attachments']);
        }
    }

    public function downloadPdf()
    {
        // Reload with withoutGlobalScopes just in case
        $this->purchaseOrder->load([
            'supplier',
            'location.branch',
            'items.inventoryItem.unit',
        ]);

        $pdf = PDF::loadView('inventory::pdfs.purchase-order', [
            'purchaseOrder' => $this->purchaseOrder
        ]);

        $pdf->getDomPDF()->set_option('defaultFont', 'Arial');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, "PO-{$this->purchaseOrder->po_number}.pdf");
    }

    public function render()
    {
        return view('inventory::livewire.purchase-order.view-purchase-order');
    }
} 
