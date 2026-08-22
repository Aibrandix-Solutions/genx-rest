<?php

namespace Modules\Inventory\Livewire\Stock;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryDisposal;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;

class ConsumptionReport extends Component
{
    use WithPagination;

    public string $startDate = '';
    public string $endDate = '';
    public string $locationFilter = 'all';
    public ?int $itemFilter = null;
    public string $search = '';
    public string $viewMode = 'summary'; // summary | detail
    public int $perPage = 20;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'locationFilter' => ['except' => 'all'],
        'itemFilter' => ['except' => null],
        'search' => ['except' => ''],
        'viewMode' => ['except' => 'summary'],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatingStartDate(): void { $this->resetPage(); }
    public function updatingEndDate(): void { $this->resetPage(); }
    public function updatingLocationFilter(): void { $this->resetPage(); }
    public function updatingItemFilter(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingViewMode(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['locationFilter', 'itemFilter', 'search']);
        $this->locationFilter = 'all';
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * Base filter applied to inventory_consumptions for the report.
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
            $query->whereHas('item', function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('item_code', 'like', $term);
            });
        }

        return $query;
    }

    /**
     * Per-item summary: opening stock (max stock_before in window),
     * total consumed, and closing stock (latest stock_after in window or
     * current on-hand if no entries).
     */
    public function getSummaryRowsProperty()
    {
        $base = $this->baseQuery();

        // Aggregate per item: total consumed, first entry id, last entry id.
        $aggregates = (clone $base)
            ->selectRaw('inventory_item_id,
                SUM(quantity) as total_consumed,
                COUNT(*) as entries,
                MIN(id) as first_id,
                MAX(id) as last_id,
                MAX(consumption_date) as last_date,
                MIN(consumption_date) as first_date')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_consumed')
            ->paginate($this->perPage, ['*'], 'summaryPage');

        $itemIds = collect($aggregates->items())->pluck('inventory_item_id')->all();

        // Pull `stock_before` of the earliest entry and `stock_after` of the latest entry per item.
        $firstIds = collect($aggregates->items())->pluck('first_id')->all();
        $lastIds = collect($aggregates->items())->pluck('last_id')->all();

        $beforeByFirstId = InventoryConsumption::whereIn('id', $firstIds)
            ->pluck('stock_before', 'id');
        $afterByLastId = InventoryConsumption::whereIn('id', $lastIds)
            ->pluck('stock_after', 'id');

        // Items map (with unit) for display.
        $items = InventoryItem::with('unit:id,symbol,name')
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        // Current on-hand stock per item (used when snapshots are missing).
        $currentStock = InventoryStock::whereIn('inventory_item_id', $itemIds)
            ->selectRaw('inventory_item_id, SUM(quantity) as on_hand')
            ->groupBy('inventory_item_id')
            ->pluck('on_hand', 'inventory_item_id');

        $rows = collect($aggregates->items())->map(function ($row) use ($beforeByFirstId, $afterByLastId, $items, $currentStock) {
            $item = $items->get($row->inventory_item_id);
            $opening = $beforeByFirstId->get($row->first_id);
            $closing = $afterByLastId->get($row->last_id);

            // Fallbacks if snapshots are missing on legacy rows.
            $consumed = (float) $row->total_consumed;
            $closingFloat = $closing !== null ? (float) $closing : (float) ($currentStock->get($row->inventory_item_id) ?? 0);
            $openingFloat = $opening !== null ? (float) $opening : ($closingFloat + $consumed);

            return (object) [
                'item' => $item,
                'item_id' => $row->inventory_item_id,
                'item_name' => $item->name ?? '--',
                'item_code' => $item->item_code ?? null,
                'unit_symbol' => optional($item?->unit)->symbol,
                'opening' => $openingFloat,
                'consumed' => $consumed,
                'closing' => $closingFloat,
                'entries' => (int) $row->entries,
                'first_date' => $row->first_date,
                'last_date' => $row->last_date,
            ];
        });

        return [
            'rows' => $rows,
            'paginator' => $aggregates,
        ];
    }

    /**
     * Detailed entry list with stock_before / quantity / stock_after columns.
     */
    public function getDetailRowsProperty()
    {
        return $this->baseQuery()
            ->with(['item.unit:id,symbol,name', 'location', 'addedBy:id,name'])
            ->orderByDesc('consumption_date')
            ->orderByDesc('id')
            ->paginate($this->perPage, ['*'], 'detailPage');
    }

    /**
     * Top-level totals shown in stat cards.
     */
    public function getTotalsProperty(): array
    {
        $base = $this->baseQuery();
        $totalConsumed = (float) (clone $base)->sum('quantity');
        $entries = (int) (clone $base)->count();
        $itemsCount = (int) (clone $base)->distinct('inventory_item_id')->count('inventory_item_id');

        return [
            'consumed' => $totalConsumed,
            'entries' => $entries,
            'items' => $itemsCount,
        ];
    }

    /**
     * Disposal totals for the same date/branch/item filters.
     */
    protected function disposalBaseQuery()
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
            $query->whereHas('item', function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('item_code', 'like', $term);
            });
        }

        return $query;
    }

    /**
     * Per-item disposal summary for the report.
     */
    public function getDisposalSummaryRowsProperty()
    {
        $base = $this->disposalBaseQuery();

        $aggregates = (clone $base)
            ->selectRaw('inventory_item_id,
                SUM(quantity) as total_disposed,
                COUNT(*) as entries,
                MIN(disposal_date) as first_date,
                MAX(disposal_date) as last_date')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_disposed')
            ->paginate($this->perPage, ['*'], 'disposalSummaryPage');

        $itemIds = collect($aggregates->items())->pluck('inventory_item_id')->all();

        $items = InventoryItem::with('unit:id,symbol,name')
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $rows = collect($aggregates->items())->map(function ($row) use ($items) {
            $item = $items->get($row->inventory_item_id);
            return (object) [
                'item_id'     => $row->inventory_item_id,
                'item_name'   => $item->name ?? '--',
                'item_code'   => $item->item_code ?? null,
                'unit_symbol' => optional($item?->unit)->symbol,
                'disposed'    => (float) $row->total_disposed,
                'entries'     => (int) $row->entries,
                'first_date'  => $row->first_date,
                'last_date'   => $row->last_date,
            ];
        });

        return [
            'rows'      => $rows,
            'paginator' => $aggregates,
        ];
    }

    /**
     * Detailed disposal entry list.
     */
    public function getDisposalDetailRowsProperty()
    {
        return $this->disposalBaseQuery()
            ->with(['item.unit:id,symbol,name', 'location', 'addedBy:id,name'])
            ->orderByDesc('disposal_date')
            ->orderByDesc('id')
            ->paginate($this->perPage, ['*'], 'disposalDetailPage');
    }

    /**
     * Disposal top-level totals.
     */
    public function getDisposalTotalsProperty(): array
    {
        $base = $this->disposalBaseQuery();
        return [
            'disposed' => (float) (clone $base)->sum('quantity'),
            'entries'  => (int) (clone $base)->count(),
            'items'    => (int) (clone $base)->distinct('inventory_item_id')->count('inventory_item_id'),
        ];
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

    public function render()
    {
        $summary = $this->summaryRows;
        $disposalSummary = $this->disposalSummaryRows;

        return view('inventory::livewire.stock.consumption-report', [
            'summaryRows'          => $summary['rows'],
            'summaryPaginator'     => $summary['paginator'],
            'detailRows'           => $this->detailRows,
            'totals'               => $this->totals,
            'locations'            => $this->locations,
            'items'                => $this->items,
            'disposalSummaryRows'  => $disposalSummary['rows'],
            'disposalSummaryPaginator' => $disposalSummary['paginator'],
            'disposalDetailRows'   => $this->disposalDetailRows,
            'disposalTotals'       => $this->disposalTotals,
        ]);
    }
}
