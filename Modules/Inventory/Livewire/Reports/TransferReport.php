<?php

namespace Modules\Inventory\Livewire\Reports;

use App\Models\Branch;
use Livewire\Component;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Exports\TransferReportExport;
use Modules\Inventory\Services\TransferReportService;
use Maatwebsite\Excel\Facades\Excel;

class TransferReport extends Component
{
    public string $reportType = 'item';

    public string $startDate = '';

    public string $endDate = '';

    public string $branchFilter = 'all';

    public string $fromLocationFilter = 'all';

    public string $toLocationFilter = 'all';

    public string $search = '';

    /** @var array<int> */
    public array $selectedItemIds = [];

    public bool $showViewModal = false;

    public $selectedTransfer = null;

    protected $queryString = [
        'reportType' => ['except' => 'item'],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
        'fromLocationFilter' => ['except' => 'all'],
        'toLocationFilter' => ['except' => 'all'],
        'search' => ['except' => ''],
        'selectedItemIds' => ['except' => []],
    ];

    public function mount(TransferReportService $reportService): void
    {
        if ($this->startDate === '') {
            $this->startDate = $reportService->defaultStartDate();
        }

        if ($this->endDate === '') {
            $this->endDate = $reportService->defaultEndDate();
        }

        $this->reportType = $reportService->normalizeReportType($this->reportType);
    }

    public function updatedReportType($value): void
    {
        $this->reportType = app(TransferReportService::class)->normalizeReportType($value);
    }

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

    public function clearFilters(TransferReportService $reportService): void
    {
        $this->startDate = $reportService->defaultStartDate();
        $this->endDate = $reportService->defaultEndDate();
        $this->branchFilter = 'all';
        $this->fromLocationFilter = 'all';
        $this->toLocationFilter = 'all';
        $this->search = '';
        $this->selectedItemIds = [];
    }

    public function viewTransfer(int $transferId): void
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $this->selectedTransfer = InventoryTransfer::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('status', 'completed')
            ->with([
                'sourceBranch',
                'destinationBranch',
                'sourceLocation',
                'destinationLocation',
                'createdBy',
                'confirmedBy',
                'items.sourceItem.unit',
                'items.destinationItem.unit',
                'items.unit',
            ])
            ->findOrFail($transferId);

        $this->showViewModal = true;
    }

    public function updatedShowViewModal($value): void
    {
        if (! $value) {
            $this->selectedTransfer = null;
        }
    }

    protected function filters(TransferReportService $reportService): array
    {
        return $reportService->parseFiltersFromInput([
            'reportType' => $this->reportType,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'branchFilter' => $this->branchFilter,
            'fromLocationFilter' => $this->fromLocationFilter,
            'toLocationFilter' => $this->toLocationFilter,
            'search' => $this->search,
            'selectedItemIds' => $this->selectedItemIds,
        ]);
    }

    public function export(TransferReportService $reportService)
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $restaurantId = (int) restaurant()->id;
        $filters = $this->filters($reportService);
        $rows = $reportService->getRows($restaurantId, $filters);

        return Excel::download(
            new TransferReportExport($rows, $filters['report_type'], $reportService),
            'transfer-report-' . $filters['report_type'] . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function render(TransferReportService $reportService)
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $restaurantId = (int) restaurant()->id;
        $filters = $this->filters($reportService);
        $rows = $reportService->getRows($restaurantId, $filters);
        $summary = $reportService->getSummaryTotals($rows, $filters['report_type']);

        $branches = Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = $reportService->getFilterLocations($restaurantId, $this->branchFilter);

        $inventoryItems = InventoryItem::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get(['id', 'name', 'item_code']);

        return view('inventory::livewire.reports.transfer-report', [
            'rows' => $rows,
            'summary' => $summary,
            'branches' => $branches,
            'locations' => $locations,
            'inventoryItems' => $inventoryItems,
            'branchLabel' => $reportService->resolveBranchLabel($restaurantId, $this->branchFilter),
            'fromLocationLabel' => $reportService->resolveLocationFilterLabel($restaurantId, $this->fromLocationFilter),
            'toLocationLabel' => $reportService->resolveLocationFilterLabel($restaurantId, $this->toLocationFilter),
            'reportService' => $reportService,
        ]);
    }
}
