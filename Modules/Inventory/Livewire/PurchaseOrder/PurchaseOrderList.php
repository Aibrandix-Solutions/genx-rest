<?php

namespace Modules\Inventory\Livewire\PurchaseOrder;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\Supplier;
use Illuminate\Support\Carbon;
use App\Scopes\BranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Modules\Inventory\Notifications\SendPurchaseOrder;
use Modules\Inventory\Services\PurchaseOrderService;

class PurchaseOrderList extends Component
{
    use WithPagination;

    public $search = '';
    public $supplierId;
    public $status = '';
    public $startDate = null;
    public $endDate = null;
    public $perPage = 20;
    public $locationFilter = '';
    public $confirmingDeletion = false;
    public $purchaseOrderToDelete;
    public $confirmingSend = false;
    public $purchaseOrderToSend;
    public $confirmingCancel = false;
    public $purchaseOrderToCancel;
    public $showPaymentModal = false;
    public $purchaseIdForPayment = null;

    protected $listeners = [
        'purchaseOrderSaved' => '$refresh',
        'purchaseOrderSent' => '$refresh',
        'purchaseOrderCancelled' => '$refresh',
        'purchaseOrderPaymentSaved' => '$refresh',
        'recordPayment' => 'openPaymentModal',
        'paymentRecorded' => 'closePaymentModal',
        'refreshPurchaseList' => '$refresh',
    ];

    public function mount()
    {
        //
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSupplierId()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'supplierId', 'status', 'startDate', 'endDate', 'locationFilter']);
        $this->resetPage();
    }

    protected function basePurchaseOrderQuery()
    {
        $query = PurchaseOrder::withoutGlobalScope(BranchScope::class)
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id));

        if ($this->locationFilter !== '' && $this->locationFilter !== null) {
            $query->where('location_id', $this->locationFilter);
        }

        return $query;
    }

    protected function findPurchaseOrder(int $purchaseOrderId): PurchaseOrder
    {
        return $this->basePurchaseOrderQuery()->findOrFail($purchaseOrderId);
    }

    public function confirmDelete(int $purchaseOrderId)
    {
        $this->purchaseOrderToDelete = $this->findPurchaseOrder($purchaseOrderId);
        $this->confirmingDeletion = true;
    }

    public function delete(PurchaseOrderService $purchaseOrderService)
    {
        abort_if(!user_can('Delete Purchase Order'), 403);

        if ($this->purchaseOrderToDelete) {
            try {
                $purchaseOrderService->deletePurchaseOrder($this->purchaseOrderToDelete);
                $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.deleted_successfully'));
            } catch (\Throwable $e) {
                $this->dispatch('notify-error', $e->getMessage());
            }
        }

        $this->confirmingDeletion = false;
        $this->purchaseOrderToDelete = null;
    }

    public function confirmSend(int $purchaseOrderId)
    {
        $this->purchaseOrderToSend = $this->findPurchaseOrder($purchaseOrderId);
        $this->confirmingSend = true;
    }

    public function send()
    {
        abort_if(!user_can('Update Purchase Order'), 403);

        if ($this->purchaseOrderToSend) {
            $this->purchaseOrderToSend->update(['status' => 'sent']);
            $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.sent_successfully'));
            $this->purchaseOrderToSend->supplier->notify(new SendPurchaseOrder($this->purchaseOrderToSend));
        }

        $this->confirmingSend = false;
        $this->purchaseOrderToSend = null;
    }

    public function confirmCancel(int $purchaseOrderId)
    {
        $this->purchaseOrderToCancel = $this->findPurchaseOrder($purchaseOrderId);
        $this->confirmingCancel = true;
    }

    public function cancel()
    {
        abort_if(!user_can('Update Purchase Order'), 403);

        if ($this->purchaseOrderToCancel) {
            abort_if(
                in_array($this->purchaseOrderToCancel->status, ['received', 'cancelled']),
                403,
                'Cannot cancel a received or already-cancelled purchase order.'
            );
            $this->purchaseOrderToCancel->update(['status' => 'cancelled']);
            $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.cancelled_successfully'));
        }

        $this->confirmingCancel = false;
        $this->purchaseOrderToCancel = null;
    }

    public function downloadPdf(int $purchaseOrderId)
    {
        $purchaseOrder = $this->findPurchaseOrder($purchaseOrderId);
        $purchaseOrder->load([
            'supplier',
            'location.branch',
            'items.inventoryItem.unit',
            'items.inventoryItem.category',
            'creator',
            'payments.account',
            'attachments',
        ]);
        
        // Configure PDF
        $pdf = PDF::loadView('inventory::pdfs.purchase-order', [
            'purchaseOrder' => $purchaseOrder
        ])->setPaper('a4');
        
        // Set additional PDF options for better font handling
        $pdf->getDomPDF()->set_option('defaultFont', 'Arial');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, "PO-{$purchaseOrder->po_number}.pdf");
    }

    protected function getStats()
    {
        $query = $this->basePurchaseOrderQuery();

        return [
            'total_orders' => $query->count(),
            'pending_orders' => $query->clone()
                ->whereIn('status', ['ordered', 'pending'])
                ->count(),
            'completed_orders' => $query->clone()
                ->where('status', 'received')
                ->count()
        ];
    }

    public function openPaymentModal($purchaseId)
    {
        $this->purchaseIdForPayment = $purchaseId;
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->purchaseIdForPayment = null;
        $this->dispatch('notify-success', trans('inventory::modules.payments.payment_recorded'));
    }

    public function render()
    {
        $query = $this->basePurchaseOrderQuery()
            ->with(['supplier', 'items.inventoryItem', 'payments', 'branch', 'location'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('id', 'like', '%' . $this->search . '%')
                        ->orWhere('invoice_no', 'like', '%' . $this->search . '%')
                        ->orWhere('po_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->startDate && $this->endDate, function($query) {
                $query->whereBetween('order_date', [$this->startDate, $this->endDate]);
            })
            ->latest();

        return view('inventory::livewire.purchase-order.purchase-order-list', [
            'purchaseOrders' => $query->paginate($this->perPage),
            'suppliers' => Supplier::where('restaurant_id', restaurant()->id)
                ->orderBy('name')
                ->get(),
            'locations' => PurchaseLocation::getForRestaurant(restaurant()->id),
            'statuses' => [
                'ordered' => trans('inventory::modules.purchaseOrder.status.ordered'),
                'pending' => trans('inventory::modules.purchaseOrder.status.pending'),
                'received' => trans('inventory::modules.purchaseOrder.status.received'),
                'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
            ],
            'stats' => $this->getStats(),
        ]);
    }


    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\Inventory\Exports\PurchaseOrderExport(
                $this->search,
                $this->startDate,
                $this->endDate,
                $this->supplierId,
                $this->status,
                $this->locationFilter,
            ),
            'purchases.xlsx'
        );
    }
} 