<?php

namespace Modules\Inventory\Livewire\Reports;

use App\Models\Branch;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Exports\ItemPurchasesReportExport;
use Modules\Inventory\Services\ItemPurchasesReportService;
use Maatwebsite\Excel\Facades\Excel;

class ItemPurchasesReport extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public string $branchFilter = 'all';

    public string $locationFilter = 'all';

    public string $categoryFilter = 'all';

    /** @var array<int> */
    public array $selectedItemIds = [];

    /** @var array<int, string> */
    public array $selectedItemCodes = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
        'locationFilter' => ['except' => 'all'],
        'categoryFilter' => ['except' => 'all'],
        'selectedItemIds' => ['except' => []],
        'selectedItemCodes' => ['except' => []],
    ];

    public function toggleSelectedItem(int $itemId): void
    {
        if (in_array($itemId, $this->selectedItemIds, true)) {
            $this->selectedItemIds = array_values(array_filter(
                $this->selectedItemIds,
                fn (int $id) => $id !== $itemId
            ));
        } else {
            $this->selectedItemIds[] = $itemId;
        }
    }

    public function toggleSelectedItemCode(string $itemCode): void
    {
        $itemCode = trim($itemCode);

        if ($itemCode === '') {
            return;
        }

        if (in_array($itemCode, $this->selectedItemCodes, true)) {
            $this->selectedItemCodes = array_values(array_filter(
                $this->selectedItemCodes,
                fn (string $code) => $code !== $itemCode
            ));
        } else {
            $this->selectedItemCodes[] = $itemCode;
        }
    }

    public function updatedBranchFilter(): void
    {
        if ($this->locationFilter === 'all') {
            return;
        }

        $location = PurchaseLocation::query()
            ->where('restaurant_id', restaurant()->id)
            ->find((int) $this->locationFilter);

        if (! $location) {
            $this->locationFilter = 'all';

            return;
        }

        if ($this->branchFilter !== 'all'
            && $location->type === 'branch'
            && (int) $location->branch_id !== (int) $this->branchFilter) {
            $this->locationFilter = 'all';
        }
    }

    public function clearFilters(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->branchFilter = 'all';
        $this->locationFilter = 'all';
        $this->categoryFilter = 'all';
        $this->selectedItemIds = [];
        $this->selectedItemCodes = [];
    }

    protected function filters(): array
    {
        return app(ItemPurchasesReportService::class)->parseFiltersFromInput([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'branchFilter' => $this->branchFilter,
            'locationFilter' => $this->locationFilter,
            'categoryFilter' => $this->categoryFilter,
            'selectedItemIds' => $this->selectedItemIds,
            'selectedItemCodes' => $this->selectedItemCodes,
        ]);
    }

    public function export(ItemPurchasesReportService $reportService)
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $restaurantId = (int) restaurant()->id;
        $filters = $this->filters();
        $rows = $reportService->getAggregatedRows($restaurantId, $filters);

        return Excel::download(
            new ItemPurchasesReportExport($rows, $reportService),
            'item-purchases-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function render(ItemPurchasesReportService $reportService)
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $restaurantId = (int) restaurant()->id;
        $filters = $this->filters();

        $rows = $reportService->getAggregatedRows($restaurantId, $filters);
        $summary = $reportService->getSummaryTotals($restaurantId, $filters, $rows);

        $branches = Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = $reportService->getFilterLocations($restaurantId, $this->branchFilter);

        $categories = InventoryItemCategory::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $inventoryItems = InventoryItem::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name', 'item_code']);

        $codedItems = $inventoryItems->filter(fn ($item) => filled($item->item_code))->values();

        return view('inventory::livewire.reports.item-purchases-report', [
            'rows' => $rows,
            'summary' => $summary,
            'branches' => $branches,
            'locations' => $locations,
            'categories' => $categories,
            'inventoryItems' => $inventoryItems,
            'codedItems' => $codedItems,
            'branchLabel' => $reportService->resolveBranchLabel($restaurantId, $this->branchFilter),
            'locationLabel' => $reportService->resolveLocationLabel($restaurantId, $this->locationFilter),
            'categoryLabel' => $reportService->resolveCategoryLabel(
                $restaurantId,
                $this->categoryFilter === 'all' ? null : (int) $this->categoryFilter
            ),
            'reportService' => $reportService,
        ]);
    }
}
