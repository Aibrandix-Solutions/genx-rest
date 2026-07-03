<?php

namespace Modules\Inventory\Livewire\PurchaseReturn;

use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\PurchaseReturnExport;
use Modules\Inventory\Entities\PurchaseReturn;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\Supplier;
use App\Scopes\BranchScope;
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
    public $locationFilter = '';
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

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'supplierId', 'purchaseOrderId', 'status', 'startDate', 'endDate', 'locationFilter']);
        $this->resetPage();
    }

    protected function basePurchaseReturnQuery()
    {
        $query = PurchaseReturn::withoutGlobalScope(BranchScope::class)
            ->whereHas('supplier', fn ($q) => $q->where('restaurant_id', restaurant()->id));

        if ($this->locationFilter !== '' && $this->locationFilter !== null) {
            $query->whereHas('purchaseOrder', fn ($q) => $q
                ->withoutGlobalScope(BranchScope::class)
                ->where('location_id', $this->locationFilter));
        }

        return $query;
    }

    protected function findPurchaseReturn(int $purchaseReturnId): PurchaseReturn
    {
        return $this->basePurchaseReturnQuery()->findOrFail($purchaseReturnId);
    }

    public function export()
    {
        return Excel::download(
            new PurchaseReturnExport(
                $this->search,
                $this->startDate,
                $this->endDate,
                $this->supplierId,
                $this->purchaseOrderId,
                $this->status,
                $this->locationFilter,
            ),
            'purchase-returns.xlsx'
        );
    }

    public function confirmDelete(int $purchaseReturnId)
    {
        $this->purchaseReturnToDelete = $this->findPurchaseReturn($purchaseReturnId);
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if ($this->purchaseReturnToDelete) {
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
        $query = $this->basePurchaseReturnQuery()
            ->with(['supplier', 'purchaseOrder.location', 'items.inventoryItem', 'payments'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('purchaseOrder', function ($query) {
                            $query->withoutGlobalScope(BranchScope::class)
                                ->where('po_number', 'like', '%' . $this->search . '%');
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

        $purchaseOrdersQuery = PurchaseOrder::withoutGlobalScope(BranchScope::class)
            ->whereHas('branch', fn ($q) => $q->where('restaurant_id', restaurant()->id))
            ->whereIn('status', ['received', 'partially_received']);

        if ($this->locationFilter !== '' && $this->locationFilter !== null) {
            $purchaseOrdersQuery->where('location_id', $this->locationFilter);
        }

        return view('inventory::livewire.purchase-return.purchase-return-list', [
            'purchaseReturns' => $query->paginate($this->perPage),
            'suppliers' => Supplier::where('restaurant_id', restaurant()->id)
                ->orderBy('name')
                ->get(),
            'purchaseOrders' => $purchaseOrdersQuery->orderBy('po_number')->get(),
            'locations' => PurchaseLocation::getForRestaurant(restaurant()->id),
        ]);
    }
}
