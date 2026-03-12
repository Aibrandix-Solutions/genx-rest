<?php

namespace Modules\Inventory\Livewire\PurchaseReturn;

use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\PurchaseReturnExport;
use Modules\Inventory\Entities\PurchaseReturn;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Supplier;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PurchaseReturnList extends Component
{
    use WithPagination, LivewireAlert;

    public $search = '';
    public $perPage = 10;
    public $startDate = null;
    public $endDate = null;
    public $supplierId;
    public $purchaseOrderId;
    public $status = '';
    public $confirmingDeletion = false;
    public $purchaseReturnToDelete;

    protected $listeners = [
        'purchaseReturnSaved' => '$refresh',
        'purchaseReturnDeleted' => '$refresh',
        'purchaseReturnPaymentSaved' => '$refresh',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSupplierId()
    {
        $this->resetPage();
    }

    public function updatingPurchaseOrderId()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'supplierId', 'purchaseOrderId', 'status', 'startDate', 'endDate']);
        $this->resetPage();
    }

    public function export()
    {
        return Excel::download(new PurchaseReturnExport($this->search, $this->startDate, $this->endDate, $this->supplierId, $this->purchaseOrderId, $this->status), 'purchase-returns.xlsx');
    }

    public function confirmDelete(PurchaseReturn $purchaseReturn)
    {
        $this->purchaseReturnToDelete = $purchaseReturn;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if ($this->purchaseReturnToDelete) {
            // Check if already processed
            if ($this->purchaseReturnToDelete->status === 'completed') {
                $this->alert('error', 'Cannot delete a completed purchase return. Please reverse the stock first.');
                $this->confirmingDeletion = false;
                $this->purchaseReturnToDelete = null;
                return;
            }

            $this->purchaseReturnToDelete->delete();
            $this->alert('success', 'Purchase return deleted successfully');
            $this->dispatch('purchaseReturnDeleted');
        }

        $this->confirmingDeletion = false;
        $this->purchaseReturnToDelete = null;
    }

    public function render()
    {
        $query = PurchaseReturn::query()
            ->where('branch_id', branch()->id)
            ->with(['supplier', 'purchaseOrder', 'items.inventoryItem', 'payments'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('purchaseOrder', function ($query) {
                            $query->where('po_number', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->when($this->purchaseOrderId, function ($query) {
                $query->where('purchase_order_id', $this->purchaseOrderId);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('return_date', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->latest();

        return view('inventory::livewire.purchase-return.purchase-return-list', [
            'purchaseReturns' => $query->paginate($this->perPage),
            'suppliers' => Supplier::where('restaurant_id', restaurant()->id)
                ->orderBy('name')
                ->get(),
            'purchaseOrders' => PurchaseOrder::where('branch_id', branch()->id)
                ->whereIn('status', ['received', 'partially_received'])
                ->orderBy('po_number')
                ->get(),
        ]);
    }
}

