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

    public function show($purchaseOrder)
    {
        // Accept either an int/string ID (from event payload) or an
        // already-resolved model. Resolve manually so cross-branch records
        // (when surfaced from places like the Stock-by-Location popup)
        // still load. Restaurant guard prevents leaking across tenants.
        if (!$purchaseOrder instanceof PurchaseOrder) {
            $purchaseOrder = PurchaseOrder::withoutGlobalScopes()
                ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id))
                ->find($purchaseOrder);
        }

        if (!$purchaseOrder) {
            return;
        }

        $this->purchaseOrder = $purchaseOrder->load([
            'supplier',
            'location.branch',
            'items.inventoryItem.unit',
            'items.inventoryItem.category',
            'payments.account',
            'payments.addedBy',
            'attachments',
            'creator',
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
        // Reload with all relationships needed by the PDF view
        $this->purchaseOrder->load([
            'supplier',
            'location.branch',
            'items.inventoryItem.unit',
            'items.inventoryItem.category',
            'creator',
            'payments.account',
            'attachments',
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
