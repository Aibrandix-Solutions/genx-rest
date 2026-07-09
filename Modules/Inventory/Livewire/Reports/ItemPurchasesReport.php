<?php

namespace Modules\Inventory\Livewire\Reports;

use App\Models\Branch;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Services\ItemPurchasesReportService;

class ItemPurchasesReport extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public string $branchFilter = 'all';

    public string $categoryFilter = 'all';

    /** @var array<int> */
    public array $selectedItemIds = [];

    /** @var array<int, string> */
    public array $selectedItemCodes = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
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

    public function clearFilters(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->branchFilter = 'all';
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
            'categoryFilter' => $this->categoryFilter,
            'selectedItemIds' => $this->selectedItemIds,
            'selectedItemCodes' => $this->selectedItemCodes,
        ]);
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
            'categories' => $categories,
            'inventoryItems' => $inventoryItems,
            'codedItems' => $codedItems,
            'branchLabel' => $reportService->resolveBranchLabel($restaurantId, $this->branchFilter),
            'categoryLabel' => $reportService->resolveCategoryLabel(
                $restaurantId,
                $this->categoryFilter === 'all' ? null : (int) $this->categoryFilter
            ),
            'reportService' => $reportService,
        ]);
    }
}
