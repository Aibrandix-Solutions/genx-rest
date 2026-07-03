<?php

namespace App\Livewire\Reports;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Livewire\Reports\Concerns\HasReportBranchFilter;
use App\Services\ReportBranchScope;
use App\Support\ActivityLogPropertyFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogReport extends Component
{
    use HasReportBranchFilter;
    use WithPagination;

    public string $dateRangeType = 'last7Days';
    public $fromDate;
    public $toDate;
    public $moduleFilter = 'all';
    public $categoryFilter = 'all';
    public $eventFilter = 'all';
    public $causerFilter = 'all';
    public $search = '';
    public $perPage = 15;

    protected $queryString = [
        'dateRangeType' => ['except' => 'last7Days'],
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
        'moduleFilter' => ['except' => 'all'],
        'categoryFilter' => ['except' => 'all'],
        'eventFilter' => ['except' => 'all'],
        'causerFilter' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('View Activity Log'), 403);

        $this->mountReportBranchFilter();

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
        if (in_array($field, ['fromDate', 'toDate', 'dateRangeType', 'moduleFilter', 'categoryFilter', 'eventFilter', 'causerFilter', 'perPage', 'branchFilter'])) {
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['dateRangeType', 'fromDate', 'toDate', 'moduleFilter', 'categoryFilter', 'eventFilter', 'causerFilter', 'search', 'perPage']);
        $this->mount();
    }

    protected function buildFilteredQuery()
    {
        $query = ActivityLog::query()
            ->with(['causer', 'branch'])
            ->forCurrentRestaurant();

        ReportBranchScope::applyToColumn($query, 'activity_logs.branch_id', $this->branchFilter);

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        if ($this->moduleFilter !== 'all') {
            $query->where('module', $this->moduleFilter);
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->eventFilter !== 'all') {
            $query->where('event', $this->eventFilter);
        }

        if ($this->causerFilter !== 'all') {
            $query->where('causer_id', (int) $this->causerFilter);
        }

        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('description', 'like', $searchTerm)
                    ->orWhere('event', 'like', $searchTerm)
                    ->orWhere('causer_name', 'like', $searchTerm)
                    ->orWhere('properties', 'like', $searchTerm);
            });
        }

        return $query;
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'activity-log-' . now()->format('Ymd_His') . '.csv';
        $query = $this->buildFilteredQuery()->orderByDesc('created_at');

        $branchFilter = $this->branchFilter;

        return response()->streamDownload(function () use ($query, $branchFilter) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [ReportBranchScope::exportScopeLabel($branchFilter)]);

            fputcsv($handle, [
                'Date Time',
                'Branch',
                'User',
                'Module',
                'Category',
                'Event',
                'Description',
                'Properties',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        optional($row->created_at)->timezone(timezone())->format('Y-m-d H:i:s'),
                        $row->branch->name ?? '',
                        $row->causer_name ?? $row->causer?->name,
                        $row->module,
                        $row->category,
                        $row->event,
                        $row->description,
                        ActivityLogPropertyFormatter::formatAsText($row->properties ?? [], $row->event),
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
            'unique_users' => (clone $baseQuery)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
            'modules' => (clone $baseQuery)->distinct('module')->count('module'),
        ];

        $optionQuery = ActivityLog::query()->forCurrentRestaurant();
        ReportBranchScope::applyToColumn($optionQuery, 'activity_logs.branch_id', $this->branchFilter);

        $causerOptions = (clone $optionQuery)
            ->whereNotNull('causer_id')
            ->whereNotNull('causer_name')
            ->select('causer_id', 'causer_name')
            ->distinct()
            ->orderBy('causer_name')
            ->get();

        $moduleOptions = (clone $optionQuery)
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $categoryOptions = (clone $optionQuery)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $eventOptions = collect(ActivityEvent::cases())
            ->mapWithKeys(fn (ActivityEvent $event) => [$event->value => $event->label()]);

        $activities = $baseQuery
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.reports.activity-log', [
            'activities' => $activities,
            'summary' => $summary,
            'causerOptions' => $causerOptions,
            'moduleOptions' => $moduleOptions,
            'categoryOptions' => $categoryOptions,
            'eventOptions' => $eventOptions,
            'showBranchFilter' => $this->showBranchFilter(),
            'reportBranches' => $this->reportBranches(),
            'showBranchColumn' => $this->showBranchColumn(),
        ]);
    }
}
