<?php

namespace Modules\Inventory\Livewire\Supplier;

use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseReturn;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Services\PurchaseOrderService;

class SupplierTable extends Component
{
    use WithPagination, LivewireAlert;

    #[Reactive]
    public $search = '';
    public $showAddSupplierModal = false;
    public $showEditSupplierModal = false;
    public $confirmDeleteSupplierModal = false;
    public $supplier;

    public $showPurchasePickerModal = false;
    public $purchasePickerMode = null;
    public $purchasePickerSupplierId = null;
    public $supplierPurchaseOrders = [];
    public $selectedPurchaseOrderId = null;
    public $confirmDeletePurchaseModal = false;
    public $purchasePickerSearch = '';
    public $purchasePickerLoading = false;

    protected $listeners = ['refreshSupplierTable' => '$refresh'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function editSupplier($id)
    {
        $this->supplier = Supplier::find($id);
        $this->showEditSupplierModal = true;
    }

    public function deleteSupplier($id)
    {
        if ($this->confirmDeleteSupplierModal) {
            $supplier = Supplier::find($id);
            if ($supplier) {
                $hasActiveOrders = $supplier->orders()
                    ->whereNotIn('status', ['cancelled'])
                    ->exists();

                if ($hasActiveOrders) {
                    $this->alert('error', trans('inventory::modules.supplier.cannot_delete_has_orders'));
                    $this->confirmDeleteSupplierModal = false;
                    $this->supplier = null;
                    return;
                }

                $supplier->delete();
                $this->confirmDeleteSupplierModal = false;
                $this->supplier = null;
            }
        } else {
            $this->supplier = Supplier::find($id);
            $this->confirmDeleteSupplierModal = true;
        }
    }

    public function openPurchasePicker(int $supplierId, string $mode): void
    {
        if ($mode === 'edit') {
            abort_if(!(user_can('Update Purchase Order') || user_can('Edit Purchase Order')), 403);
        } else {
            abort_if(!user_can('Delete Purchase Order'), 403);
        }

        $this->purchasePickerSupplierId = $supplierId;
        $this->purchasePickerMode = $mode;
        $this->selectedPurchaseOrderId = null;
        $this->purchasePickerSearch = '';
        $this->supplierPurchaseOrders = [];
        $this->purchasePickerLoading = true;
        $this->showPurchasePickerModal = true;

        $this->loadPurchasePickerOrders();
    }

    protected function loadPurchasePickerOrders(): void
    {
        if (!$this->purchasePickerSupplierId) {
            $this->purchasePickerLoading = false;
            return;
        }

        $currencyId = restaurant()->currency_id;

        $this->supplierPurchaseOrders = PurchaseOrder::query()
            ->where('supplier_id', $this->purchasePickerSupplierId)
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get(['id', 'po_number', 'invoice_no', 'order_date', 'total_amount', 'status'])
            ->map(fn (PurchaseOrder $po) => [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'invoice_no' => $po->invoice_no,
                'order_date' => $po->order_date?->format('M d, Y') ?? '--',
                'total_amount' => (float) $po->total_amount,
                'total_display' => currency_format($po->total_amount, $currencyId),
                'status' => $po->status,
                'status_label' => str_replace('_', ' ', $po->status),
            ])
            ->all();

        $this->purchasePickerLoading = false;
    }

    public function closePurchasePicker(): void
    {
        $this->showPurchasePickerModal = false;
        $this->purchasePickerMode = null;
        $this->purchasePickerSupplierId = null;
        $this->supplierPurchaseOrders = [];
        $this->selectedPurchaseOrderId = null;
        $this->purchasePickerSearch = '';
        $this->purchasePickerLoading = false;
        $this->skipRender();
    }

    public function updatedPurchasePickerSearch(): void
    {
        $visibleIds = $this->filteredPurchaseOrders->pluck('id')->all();

        if ($this->selectedPurchaseOrderId && !in_array($this->selectedPurchaseOrderId, $visibleIds, true)) {
            $this->selectedPurchaseOrderId = null;
        }
    }

    public function getFilteredPurchaseOrdersProperty(): Collection
    {
        $orders = collect($this->supplierPurchaseOrders);
        $term = trim($this->purchasePickerSearch);

        if ($term === '') {
            return $orders;
        }

        $needle = strtolower($term);

        return $orders->filter(function (array $order) use ($needle) {
            return str_contains(strtolower((string) $order['po_number']), $needle)
                || str_contains(strtolower((string) ($order['invoice_no'] ?? '')), $needle)
                || str_contains((string) $order['id'], $needle)
                || str_contains(strtolower((string) $order['status_label']), $needle)
                || str_contains(strtolower((string) $order['order_date']), $needle)
                || str_contains(strtolower((string) $order['total_display']), $needle);
        })->values();
    }

    public function proceedPurchasePicker()
    {
        if (!$this->selectedPurchaseOrderId) {
            $this->alert('error', trans('inventory::modules.supplier.select_purchase_required'));
            return;
        }

        $purchaseOrder = PurchaseOrder::query()
            ->where('supplier_id', $this->purchasePickerSupplierId)
            ->find($this->selectedPurchaseOrderId);

        if (!$purchaseOrder) {
            $this->alert('error', trans('inventory::modules.purchaseOrder.not_found'));
            return;
        }

        if ($this->purchasePickerMode === 'edit') {
            if ($purchaseOrder->status === 'received' && !user_can('Edit Received Purchase')) {
                $this->alert('error', trans('inventory::modules.purchaseOrder.cannot_edit_received'));
                return;
            }

            $editUrl = route('purchases.edit', [
                'purchase' => $purchaseOrder->id,
                'return' => 'suppliers',
            ]);

            $this->closePurchasePicker();
            $this->redirect($editUrl, navigate: true);
            return;
        }

        if ($this->purchasePickerMode === 'delete') {
            $this->showPurchasePickerModal = false;
            $this->confirmDeletePurchaseModal = true;
        }
    }

    public function deleteSelectedPurchase(PurchaseOrderService $purchaseOrderService): void
    {
        abort_if(!user_can('Delete Purchase Order'), 403);

        if (!$this->selectedPurchaseOrderId || !$this->purchasePickerSupplierId) {
            return;
        }

        $purchaseOrder = PurchaseOrder::query()
            ->where('supplier_id', $this->purchasePickerSupplierId)
            ->find($this->selectedPurchaseOrderId);

        if (!$purchaseOrder) {
            $this->alert('error', trans('inventory::modules.purchaseOrder.not_found'));
            $this->resetPurchaseDeleteState();
            return;
        }

        try {
            $purchaseOrderService->deletePurchaseOrder($purchaseOrder);
            $this->alert('success', trans('inventory::modules.purchaseOrder.deleted_successfully'));
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }

        $this->resetPurchaseDeleteState();
    }

    public function resetPurchaseDeleteState(): void
    {
        $this->confirmDeletePurchaseModal = false;
        $this->closePurchasePicker();
    }

    protected function supplierBalancesFor(array $supplierIds): array
    {
        if ($supplierIds === []) {
            return [];
        }

        $purchased = PurchaseOrder::query()
            ->whereIn('supplier_id', $supplierIds)
            ->where('status', 'received')
            ->groupBy('supplier_id')
            ->selectRaw('supplier_id, COALESCE(SUM(total_amount), 0) as total')
            ->pluck('total', 'supplier_id');

        $paid = SupplierPayment::query()
            ->whereIn('supplier_id', $supplierIds)
            ->whereNull('purchase_return_id')
            ->groupBy('supplier_id')
            ->selectRaw('supplier_id, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'supplier_id');

        $returned = PurchaseReturn::query()
            ->whereIn('supplier_id', $supplierIds)
            ->groupBy('supplier_id')
            ->selectRaw('supplier_id, COALESCE(SUM(total_amount), 0) as total')
            ->pluck('total', 'supplier_id');

        $balances = [];
        foreach ($supplierIds as $id) {
            $balances[$id] = (float) ($purchased[$id] ?? 0)
                - (float) ($paid[$id] ?? 0)
                - (float) ($returned[$id] ?? 0);
        }

        return $balances;
    }

    public function render()
    {
        $term = trim($this->search);

        $suppliers = Supplier::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->withCount('orders')
            ->paginate(10);

        return view('inventory::livewire.supplier.supplier-table', [
            'suppliers' => $suppliers,
            'balances' => $this->supplierBalancesFor($suppliers->pluck('id')->all()),
        ]);
    }
}
