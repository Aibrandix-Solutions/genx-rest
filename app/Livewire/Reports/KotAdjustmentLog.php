<?php

namespace App\Livewire\Reports;

use App\Models\KotItemAdjustment;
use Livewire\Component;
use Livewire\WithPagination;

class KotAdjustmentLog extends Component
{
    use WithPagination;

    public $fromDate;
    public $toDate;
    public $actionType = 'all';
    public $search = '';
    public $perPage = 15;

    protected $queryString = [
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
        'actionType' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->fromDate = $this->fromDate ?: now()->subDays(7)->format('Y-m-d');
        $this->toDate = $this->toDate ?: now()->format('Y-m-d');
    }

    public function updated($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'actionType', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['fromDate', 'toDate', 'actionType', 'search', 'perPage']);
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


