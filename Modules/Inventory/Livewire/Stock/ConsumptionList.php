<?php

namespace Modules\Inventory\Livewire\Stock;

use App\Models\Branch;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryItem;

class ConsumptionList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $branchFilter = 'all';
    public ?int $itemFilter = null;
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
        'itemFilter' => ['except' => null],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingBranchFilter(): void { $this->resetPage(); }
    public function updatingItemFilter(): void { $this->resetPage(); }
    public function updatingStartDate(): void { $this->resetPage(); }
    public function updatingEndDate(): void { $this->resetPage(); }

    #[On('consumptionRecorded')]
    public function refreshAfterRecord(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'branchFilter', 'itemFilter']);
        $this->branchFilter = 'all';
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * Base query honoring all filters except pagination.
     */
    protected function baseQuery()
    {
        $query = InventoryConsumption::query()
            ->where('restaurant_id', restaurant()->id);

        if ($this->startDate) {
            $query->whereDate('consumption_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('consumption_date', '<=', $this->endDate);
        }
        if ($this->branchFilter !== 'all' && $this->branchFilter !== '') {
            $query->where('branch_id', $this->branchFilter);
        }
        if (!empty($this->itemFilter)) {
            $query->where('inventory_item_id', $this->itemFilter);
        }
        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('item', function ($qq) use ($term) {
                    $qq->where('name', 'like', $term)
                        ->orWhere('item_code', 'like', $term);
                })->orWhere('note', 'like', $term);
            });
        }

        return $query;
    }

    public function getConsumptionsProperty()
    {
        return $this->baseQuery()
            ->with(['item.unit', 'branch:id,name', 'menuItems:id,item_name', 'addedBy:id,name'])
            ->orderByDesc('consumption_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    /**
     * Per-item totals for the filtered range.
     */
    public function getItemTotalsProperty()
    {
        return $this->baseQuery()
            ->selectRaw('inventory_item_id, SUM(quantity) as total_qty, COUNT(*) as entries')
            ->groupBy('inventory_item_id')
            ->with(['item:id,name,item_code,unit_id', 'item.unit:id,symbol,name'])
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get();
    }

    /**
     * Per-branch breakdown.
     */
    public function getBranchTotalsProperty()
    {
        return $this->baseQuery()
            ->selectRaw('branch_id, SUM(quantity) as total_qty, COUNT(*) as entries')
            ->groupBy('branch_id')
            ->with(['branch:id,name'])
            ->orderByDesc('total_qty')
            ->get();
    }

    /**
     * Menu-wise usage (sum of consumption.quantity per linked menu item).
     */
    public function getMenuTotalsProperty()
    {
        $rows = $this->baseQuery()
            ->join('inventory_consumption_menu_item as icmi', 'icmi.inventory_consumption_id', '=', 'inventory_consumptions.id')
            ->join('menu_items', 'menu_items.id', '=', 'icmi.menu_item_id')
            ->selectRaw('menu_items.id as menu_item_id, menu_items.item_name as menu_item_name, SUM(inventory_consumptions.quantity) as total_qty, COUNT(*) as entries')
            ->groupBy('menu_items.id', 'menu_items.item_name')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get();

        return $rows;
    }

    public function getBranchesProperty()
    {
        return Branch::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getItemsProperty()
    {
        return InventoryItem::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'item_code']);
    }

    public function getStatsProperty(): array
    {
        $base = $this->baseQuery();

        return [
            'total_qty' => (float) (clone $base)->sum('quantity'),
            'entries' => (int) (clone $base)->count(),
            'unique_items' => (int) (clone $base)->distinct('inventory_item_id')->count('inventory_item_id'),
            'unique_branches' => (int) (clone $base)->distinct('branch_id')->count('branch_id'),
        ];
    }

    public function render()
    {
        return view('inventory::livewire.stock.consumption-list', [
            'consumptions' => $this->consumptions,
            'itemTotals' => $this->itemTotals,
            'branchTotals' => $this->branchTotals,
            'menuTotals' => $this->menuTotals,
            'branches' => $this->branches,
            'items' => $this->items,
            'stats' => $this->stats,
        ]);
    }
}
