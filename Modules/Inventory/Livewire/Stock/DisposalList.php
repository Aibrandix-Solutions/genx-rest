<?php

namespace Modules\Inventory\Livewire\Stock;

use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryDisposal;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\PurchaseLocation;

class DisposalList extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $locationFilter = 'all';
    public ?int   $itemFilter  = null;
    public string $startDate   = '';
    public string $endDate     = '';
    public int    $perPage     = 20;

    protected $queryString = [
        'search'       => ['except' => ''],
        'locationFilter' => ['except' => 'all'],
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
    public function updatingLocationFilter(): void { $this->resetPage(); }
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
        if ($this->locationFilter !== 'all' && $this->locationFilter !== '') {
            $query->where('location_id', $this->locationFilter);
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
            ->with(['item.unit', 'location', 'addedBy:id,name'])
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

    public function getLocationTotalsProperty()
    {
        return $this->baseQuery()
            ->selectRaw('location_id, SUM(quantity) as total_qty, COUNT(*) as entries')
            ->groupBy('location_id')
            ->with(['location'])
            ->orderByDesc('total_qty')
            ->get();
    }

    public function getLocationsProperty()
    {
        return PurchaseLocation::getForRestaurant(restaurant()->id);
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
            'unique_locations' => (int) (clone $base)->distinct('location_id')->count('location_id'),
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'locationFilter', 'itemFilter']);
        $this->locationFilter = 'all';
        $this->startDate    = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate      = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        return view('inventory::livewire.stock.disposal-list', [
            'disposals'    => $this->disposals,
            'itemTotals'   => $this->itemTotals,
            'locationTotals' => $this->locationTotals,
            'locations'      => $this->locations,
            'items'        => $this->items,
            'stats'        => $this->stats,
        ]);
    }
}
