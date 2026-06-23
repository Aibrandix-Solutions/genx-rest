<?php

namespace Modules\Inventory\Livewire\Reports;

use App\Models\Branch;
use Carbon\Carbon;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Services\ItemInventoryReportService;

class ItemInventoryReport extends Component
{
    public bool $showFilters = false;

    public string $startDate = '';

    public string $endDate = '';

    public string $branchFilter = 'all';

    public ?int $supplierId = null;

    public string $purchaseStatus = '';

    public string $paymentStatus = '';

    public string $search = '';

    public ?int $itemFilter = null;

    public string $activityType = 'all';

    public ?int $locationId = null;

    public bool $showItemDetails = false;

    public ?int $detailItemId = null;

    public string $ledgerBranchFilter = 'all';

    public string $ledgerStartDate = '';

    public string $ledgerEndDate = '';

    public function updatedSupplierId($value): void
    {
        $this->supplierId = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function updatedItemFilter($value): void
    {
        $this->itemFilter = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function updatedLocationId($value): void
    {
        $this->locationId = ($value === '' || $value === null) ? null : (int) $value;
    }

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
        'supplierId' => ['except' => null],
        'purchaseStatus' => ['except' => ''],
        'paymentStatus' => ['except' => ''],
        'search' => ['except' => ''],
        'itemFilter' => ['except' => null],
        'activityType' => ['except' => 'all'],
        'locationId' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function applySearch(): void
    {
        // Deferred filter inputs sync before this runs; re-render applies them to the report.
    }

    public function clearFilters(): void
    {
        $this->branchFilter = 'all';
        $this->supplierId = null;
        $this->purchaseStatus = '';
        $this->paymentStatus = '';
        $this->search = '';
        $this->itemFilter = null;
        $this->activityType = 'all';
        $this->locationId = null;
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function viewItemDetails(int $itemId): void
    {
        $this->detailItemId = $itemId;
        $this->ledgerBranchFilter = 'all';
        $this->ledgerStartDate = '';
        $this->ledgerEndDate = '';
        $this->showItemDetails = true;
    }

    public function closeItemDetails(): void
    {
        $this->showItemDetails = false;
        $this->detailItemId = null;
        $this->ledgerBranchFilter = 'all';
        $this->ledgerStartDate = '';
        $this->ledgerEndDate = '';
    }

    protected function filters(): array
    {
        return app(ItemInventoryReportService::class)->parseFiltersFromInput([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'branchFilter' => $this->branchFilter,
            'supplierId' => $this->supplierId,
            'purchaseStatus' => $this->purchaseStatus,
            'paymentStatus' => $this->paymentStatus,
            'search' => $this->search,
            'itemFilter' => $this->itemFilter,
            'activityType' => $this->activityType,
            'locationId' => $this->locationId,
        ]);
    }

    public function printQuery(): array
    {
        return array_filter([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'branchFilter' => $this->branchFilter,
            'supplierId' => $this->supplierId,
            'purchaseStatus' => $this->purchaseStatus,
            'paymentStatus' => $this->paymentStatus,
            'search' => $this->search,
            'itemFilter' => $this->itemFilter,
            'activityType' => $this->activityType,
            'locationId' => $this->locationId,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function render(ItemInventoryReportService $reportService)
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $restaurantId = (int) restaurant()->id;
        $filters = $this->filters();

        $summaryRows = $reportService->getItemSummaryRows($restaurantId, $filters);
        $detailSections = null;
        $ledgerRows = collect();
        $ledgerCurrentBalance = null;
        $detailItemUnit = '';

        if ($this->showItemDetails && $this->detailItemId) {
            $detailSections = $reportService->getItemDetailSections($restaurantId, $filters, $this->detailItemId);
            $ledgerRowsRaw = $reportService->getItemLedgerRows($restaurantId, $filters, $this->detailItemId);
            $ledgerRows = $reportService->filterLedgerRows($ledgerRowsRaw, [
                'branch_id' => $this->ledgerBranchFilter === 'all' ? null : (int) $this->ledgerBranchFilter,
                'start_date' => $this->ledgerStartDate,
                'end_date' => $this->ledgerEndDate,
            ]);

            $detailItem = InventoryItem::query()
                ->with('unit:id,symbol,name')
                ->where('restaurant_id', $restaurantId)
                ->find($this->detailItemId);

            $detailItemUnit = $detailItem?->unit?->symbol ?? $detailItem?->unit?->name ?? '';

            if ($ledgerRows->isNotEmpty()) {
                $ledgerCurrentBalance = $reportService->formatQuantity((float) $ledgerRows->first()->running_balance);
            }
        }

        $branches = Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $suppliers = Supplier::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $inventoryItems = InventoryItem::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name', 'item_code']);

        $locations = PurchaseLocation::getForRestaurant($restaurantId);

        $purchaseStatuses = [
            'ordered' => trans('inventory::modules.purchaseOrder.status.ordered'),
            'pending' => trans('inventory::modules.purchaseOrder.status.pending'),
            'received' => trans('inventory::modules.purchaseOrder.status.received'),
            'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
        ];

        $paymentStatuses = [
            'paid' => trans('inventory::modules.purchaseOrder.payment_status.paid'),
            'partial' => trans('inventory::modules.purchaseOrder.payment_status.partial'),
            'due' => trans('inventory::modules.purchaseOrder.payment_status.due'),
        ];

        $activityTypes = [
            'all' => trans('inventory::modules.reports.item_inventory.activity_types.all'),
            'purchases' => trans('inventory::modules.reports.item_inventory.activity_types.purchases'),
            'usage' => trans('inventory::modules.reports.item_inventory.activity_types.usage'),
            'movements' => trans('inventory::modules.reports.item_inventory.activity_types.movements'),
            'wastages' => trans('inventory::modules.reports.item_inventory.activity_types.wastages'),
        ];

        return view('inventory::livewire.reports.item-inventory-report', [
            'summaryRows' => $summaryRows,
            'detailSections' => $detailSections,
            'ledgerRows' => $ledgerRows,
            'ledgerCurrentBalance' => $ledgerCurrentBalance,
            'detailItemUnit' => $detailItemUnit,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'inventoryItems' => $inventoryItems,
            'locations' => $locations,
            'purchaseStatuses' => $purchaseStatuses,
            'paymentStatuses' => $paymentStatuses,
            'activityTypes' => $activityTypes,
            'branchLabel' => $reportService->resolveBranchLabel($restaurantId, $this->branchFilter),
            'itemLabel' => $reportService->resolveItemLabel($restaurantId, $this->itemFilter),
            'locationLabel' => $reportService->resolveLocationLabel($restaurantId, $this->locationId),
            'detailItemLabel' => $reportService->resolveItemLabel($restaurantId, $this->detailItemId),
        ]);
    }
}
