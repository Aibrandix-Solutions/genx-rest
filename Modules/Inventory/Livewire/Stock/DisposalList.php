<?php

namespace Modules\Inventory\Livewire\Stock;

use App\Models\Branch;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryDisposal;
use Modules\Inventory\Entities\InventoryItem;

class DisposalList extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $branchFilter = 'all';
    public ?int   $itemFilter  = null;
    public string $startDate   = '';
    public string $endDate     = '';
    public int    $perPage     = 20;

    protected $queryString = [
        'search'       => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
        'itemFilter'   => ['except' => null],
        'startDate'    => ['except' => ''],
        'endDate'      => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate   = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingBranchFilter(): void { $this->resetPage(); }
    public function updatingItemFilter(): void   { $this->resetPage(); }
    public function updatingStartDate(): void    { $this->resetPage(); }
    public function updatingEndDate(): void      { $this->resetPage(); }

    #[On('disposalRecorded')]
    public function refreshAfterRecord(): void
    {
        $this->resetPage();
    }

    protected function baseQuery()
    {
        $query = InventoryDisposal::query()
            ->where('restaurant_id', restaurant()->id);

        if ($this->startDate) {
            $query->whereDate('disposal_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('disposal_date', '<=', $this->endDate);
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
                })->orWhere('reason', 'like', $term);
            });
        }

        return $query;
    }

    public function getDisposalsProperty()
    {
        return $this->baseQuery()
            ->with(['item.unit', 'branch:id,name', 'addedBy:id,name'])
            ->orderByDesc('disposal_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

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

    public function getBranchTotalsProperty()
    {
        return $this->baseQuery()
            ->selectRaw('branch_id, SUM(quantity) as total_qty, COUNT(*) as entries')
            ->groupBy('branch_id')
            ->with(['branch:id,name'])
            ->orderByDesc('total_qty')
            ->get();
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
            'total_qty'       => (float) (clone $base)->sum('quantity'),
            'entries'         => (int) (clone $base)->count(),
            'unique_items'    => (int) (clone $base)->distinct('inventory_item_id')->count('inventory_item_id'),
            'unique_branches' => (int) (clone $base)->distinct('branch_id')->count('branch_id'),
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'branchFilter', 'itemFilter']);
        $this->branchFilter = 'all';
        $this->startDate    = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate      = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        return view('inventory::livewire.stock.disposal-list', [
            'disposals'    => $this->disposals,
            'itemTotals'   => $this->itemTotals,
            'branchTotals' => $this->branchTotals,
            'branches'     => $this->branches,
            'items'        => $this->items,
            'stats'        => $this->stats,
        ]);
    }
}
