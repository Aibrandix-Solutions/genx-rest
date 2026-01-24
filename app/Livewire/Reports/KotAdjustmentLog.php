<?php

namespace App\Livewire\Reports;

use App\Models\KotItemAdjustment;
use Livewire\Component;
use Livewire\WithPagination;

class KotAdjustmentLog extends Component
{
    use WithPagination;

    public string $dateRangeType = 'last7Days';
    public $fromDate;
    public $toDate;
    public $actionType = 'all';
    public $search = '';
    public $perPage = 15;

    protected $queryString = [
        'dateRangeType' => ['except' => 'last7Days'],
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
        'actionType' => ['except' => 'all'],
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
        if (in_array($field, ['fromDate', 'toDate', 'dateRangeType', 'actionType', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['dateRangeType', 'fromDate', 'toDate', 'actionType', 'search', 'perPage']);
        $this->mount();
    }

    public function render()
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

        $adjustments = $query
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.reports.kot-adjustment-log', [
            'adjustments' => $adjustments,
        ]);
    }
}


