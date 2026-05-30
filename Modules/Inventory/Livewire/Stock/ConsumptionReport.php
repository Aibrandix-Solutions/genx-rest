<?php

namespace Modules\Inventory\Livewire\Stock;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryStock;

class ConsumptionReport extends Component
{
    use WithPagination;

    public string $startDate = '';
    public string $endDate = '';
    public string $branchFilter = 'all';
    public ?int $itemFilter = null;
    public string $search = '';
    public string $viewMode = 'summary'; // summary | detail
    public int $perPage = 20;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'branchFilter' => ['except' => 'all'],
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
    public function updatingBranchFilter(): void { $this->resetPage(); }
    public function updatingItemFilter(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingViewMode(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['branchFilter', 'itemFilter', 'search']);
        $this->branchFilter = 'all';
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
        if ($this->branchFilter !== 'all' && $this->branchFilter !== '') {
            $query->where('branch_id', $this->branchFilter);
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
            ->with(['item.unit:id,symbol,name', 'branch:id,name', 'addedBy:id,name'])
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

    public function render()
    {
        $summary = $this->summaryRows;

        return view('inventory::livewire.stock.consumption-report', [
            'summaryRows' => $summary['rows'],
            'summaryPaginator' => $summary['paginator'],
            'detailRows' => $this->detailRows,
            'totals' => $this->totals,
            'branches' => $this->branches,
            'items' => $this->items,
        ]);
    }
}
