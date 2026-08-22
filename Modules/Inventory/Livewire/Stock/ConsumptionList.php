<?php

namespace Modules\Inventory\Livewire\Stock;

use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\PurchaseLocation;

class ConsumptionList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $locationFilter = 'all';
    public ?int $itemFilter = null;
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'locationFilter' => ['except' => 'all'],
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
    public function updatingLocationFilter(): void { $this->resetPage(); }
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
        $this->reset(['search', 'locationFilter', 'itemFilter']);
        $this->locationFilter = 'all';
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
                })->orWhere('note', 'like', $term);
            });
        }

        return $query;
    }

    public function getConsumptionsProperty()
    {
        return $this->baseQuery()
            ->with(['item.unit', 'location', 'menuItems:id,item_name', 'addedBy:id,name'])
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
    public function getLocationTotalsProperty()
    {
        return $this->baseQuery()
            ->selectRaw('location_id, SUM(quantity) as total_qty, COUNT(*) as entries')
            ->groupBy('location_id')
            ->with(['location'])
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
            'total_qty' => (float) (clone $base)->sum('quantity'),
            'entries' => (int) (clone $base)->count(),
            'unique_items' => (int) (clone $base)->distinct('inventory_item_id')->count('inventory_item_id'),
            'unique_locations' => (int) (clone $base)->distinct('location_id')->count('location_id'),
        ];
    }

    public function render()
    {
        return view('inventory::livewire.stock.consumption-list', [
            'consumptions' => $this->consumptions,
            'itemTotals' => $this->itemTotals,
            'locationTotals' => $this->locationTotals,
            'menuTotals' => $this->menuTotals,
            'locations' => $this->locations,
            'items' => $this->items,
            'stats' => $this->stats,
        ]);
    }
}
