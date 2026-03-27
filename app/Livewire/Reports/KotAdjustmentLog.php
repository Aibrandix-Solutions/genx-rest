<?php

namespace App\Livewire\Reports;

use App\Models\KotItemAdjustment;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Component;
use Livewire\WithPagination;

class KotAdjustmentLog extends Component
{
    use WithPagination;

    public string $dateRangeType = 'last7Days';
    public $fromDate;
    public $toDate;
    public $actionType = 'all';
    public $performedBy = 'all';
    public $search = '';
    public $perPage = 15;
    public bool $comboOnly = false;

    protected $queryString = [
        'dateRangeType' => ['except' => 'last7Days'],
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
        'actionType' => ['except' => 'all'],
        'performedBy' => ['except' => 'all'],
        'comboOnly' => ['except' => false],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        if ($this->fromDate || $this->toDate) {
            $this->dateRangeType = 'custom';
            $this->fromDate = $this->fromDate ?: now()->subDays(7)->format('Y-m-d');
            $this->toDate = $this->toDate ?: now()->format('Y-m-d');
            return;
        }

        $this->setDateRange();
    }

    public function setDateRange(): void
    {
        $now = now();

        switch ($this->dateRangeType) {
            case 'today':
                $this->fromDate = $now->copy()->startOfDay()->format('Y-m-d');
                $this->toDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;

            case 'yesterday':
                $this->fromDate = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                $this->toDate = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                break;

            case 'currentWeek':
                $this->fromDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->toDate = $now->copy()->endOfWeek()->format('Y-m-d');
                break;

            case 'lastWeek':
                $this->fromDate = $now->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->toDate = $now->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                break;

            case 'last7Days':
                $this->fromDate = $now->copy()->subDays(7)->format('Y-m-d');
                $this->toDate = $now->copy()->format('Y-m-d');
                break;

            case 'currentMonth':
                $this->fromDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->toDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;

            case 'lastMonth':
                $this->fromDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->toDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;

            case 'currentYear':
                $this->fromDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->toDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;

            case 'lastYear':
                $this->fromDate = $now->copy()->subYear()->startOfYear()->format('Y-m-d');
                $this->toDate = $now->copy()->subYear()->endOfYear()->format('Y-m-d');
                break;

            case 'custom':
            default:
                break;
        }
    }

    public function updatedDateRangeType(): void
    {
        if ($this->dateRangeType !== 'custom') {
            $this->setDateRange();
        }

        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->dateRangeType = 'custom';
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->dateRangeType = 'custom';
        $this->resetPage();
    }

    public function updated($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'dateRangeType', 'actionType', 'performedBy', 'comboOnly', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['dateRangeType', 'fromDate', 'toDate', 'actionType', 'performedBy', 'search', 'perPage', 'comboOnly']);
        $this->mount();
    }

    protected function buildFilteredQuery()
    {
        $query = KotItemAdjustment::query()
            ->with('performedBy')
            ->forCurrentRestaurant();

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        if ($this->actionType !== 'all') {
            $query->where('action', $this->actionType);
        }

        if ($this->performedBy !== 'all') {
            $query->where('performed_by', (int) $this->performedBy);
        }

        if ($this->comboOnly) {
            $query->where(function ($subQuery) {
                $subQuery->whereNotNull('note')
                    ->where('note', 'like', '%combo%');
            });
        }

        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('order_number', 'like', $searchTerm)
                    ->orWhere('formatted_order_number', 'like', $searchTerm)
                    ->orWhere('menu_item_name', 'like', $searchTerm)
                    ->orWhere('performed_by_name', 'like', $searchTerm)
                    ->orWhere('table_code', 'like', $searchTerm)
                    ->orWhere('note', 'like', $searchTerm);
            });
        }

        return $query;
    }

    protected function groupKey(KotItemAdjustment $adjustment): string
    {
        $timestamp = optional($adjustment->created_at)->format('Y-m-d H:i:s') ?? 'na';

        return implode('|', [
            (string) ($adjustment->order_id ?? 'na'),
            (string) ($adjustment->performed_by ?? 'na'),
            (string) ($adjustment->action ?? 'na'),
            trim((string) ($adjustment->note ?? '')),
            $timestamp,
        ]);
    }

    protected function groupedEvents($adjustmentsPage): array
    {
        $groups = [];

        foreach ($adjustmentsPage as $adjustment) {
            $key = $this->groupKey($adjustment);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'header' => $adjustment,
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = $adjustment;
        }

        return array_values($groups);
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'kot-adjustments-' . now()->format('Ymd_His') . '.csv';
        $query = $this->buildFilteredQuery()->orderByDesc('created_at');

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Date Time',
                'Order Number',
                'Order ID',
                'Table',
                'Action',
                'Item',
                'Variation',
                'Qty Before',
                'Qty After',
                'User',
                'Note',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        optional($row->created_at)->timezone(timezone())->format('Y-m-d H:i:s'),
                        $row->formatted_order_number ?? $row->order_number,
                        $row->order_id,
                        $row->table_code,
                        $row->action,
                        $row->menu_item_name,
                        $row->menu_item_variation_name,
                        $row->quantity_before,
                        $row->quantity_after,
                        $row->performed_by_name,
                        $row->note,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName);
    }

    public function render()
    {
        $baseQuery = $this->buildFilteredQuery();

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'deleted' => (clone $baseQuery)->where('action', 'deleted')->count(),
            'quantity_updated' => (clone $baseQuery)->where('action', 'quantity_updated')->count(),
            'deleted_from_order' => (clone $baseQuery)->where('action', 'deleted_from_order')->count(),
            'unique_orders' => (clone $baseQuery)->whereNotNull('order_id')->distinct('order_id')->count('order_id'),
            'unique_users' => (clone $baseQuery)->whereNotNull('performed_by')->distinct('performed_by')->count('performed_by'),
        ];

        $performedByOptions = KotItemAdjustment::query()
            ->forCurrentRestaurant()
            ->whereNotNull('performed_by')
            ->whereNotNull('performed_by_name')
            ->select('performed_by', 'performed_by_name')
            ->distinct()
            ->orderBy('performed_by_name')
            ->get();

        $adjustments = $baseQuery
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $groupedAdjustments = $this->groupedEvents($adjustments->getCollection());

        return view('livewire.reports.kot-adjustment-log', [
            'adjustments' => $adjustments,
            'groupedAdjustments' => $groupedAdjustments,
            'summary' => $summary,
            'performedByOptions' => $performedByOptions,
        ]);
    }
}


