<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryDisposal;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryStock;

class InventoryStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Stock'), 403);
        return view('inventory::stock.index');
    }

    /**
     * Display the inventory consumption list.
     */
    public function consumption()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Stock'), 403);
        return view('inventory::stock.consumption');
    }

    /**
     * Display the inventory consumption report
     * (per-item before/consumed/after view).
     */
    public function consumptionReport()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Stock'), 403);
        return view('inventory::stock.consumption-report');
    }

    /**
     * Print-friendly consumption report. Renders the same data the
     * `ConsumptionReport` Livewire component shows (using the same filters),
     * but without pagination so all matching rows print on a single page.
     */
    public function consumptionReportPrint(Request $request)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Stock'), 403);

        $startDate = $request->query('startDate') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->query('endDate') ?: Carbon::now()->format('Y-m-d');
        $branchFilter = (string) $request->query('branchFilter', 'all');
        $itemFilter = $request->query('itemFilter');
        $itemFilter = is_numeric($itemFilter) ? (int) $itemFilter : null;
        $search = (string) $request->query('search', '');
        $viewMode = $request->query('viewMode') === 'detail' ? 'detail' : 'summary';

        $base = InventoryConsumption::query()
            ->where('restaurant_id', restaurant()->id)
            ->whereDate('consumption_date', '>=', $startDate)
            ->whereDate('consumption_date', '<=', $endDate);

        if ($branchFilter !== 'all' && $branchFilter !== '') {
            $base->where('branch_id', $branchFilter);
        }
        if (!empty($itemFilter)) {
            $base->where('inventory_item_id', $itemFilter);
        }
        if (trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $base->whereHas('item', function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('item_code', 'like', $term);
            });
        }

        $totals = [
            'consumed' => (float) (clone $base)->sum('quantity'),
            'entries' => (int) (clone $base)->count(),
            'items' => (int) (clone $base)->distinct('inventory_item_id')->count('inventory_item_id'),
        ];

        $summaryRows = collect();
        $detailRows = collect();

        if ($viewMode === 'summary') {
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
                ->get();

            $itemIds = $aggregates->pluck('inventory_item_id')->all();
            $firstIds = $aggregates->pluck('first_id')->all();
            $lastIds = $aggregates->pluck('last_id')->all();

            $beforeByFirstId = InventoryConsumption::whereIn('id', $firstIds)
                ->pluck('stock_before', 'id');
            $afterByLastId = InventoryConsumption::whereIn('id', $lastIds)
                ->pluck('stock_after', 'id');

            $items = InventoryItem::with('unit:id,symbol,name')
                ->whereIn('id', $itemIds)
                ->get()
                ->keyBy('id');

            $currentStock = InventoryStock::whereIn('inventory_item_id', $itemIds)
                ->selectRaw('inventory_item_id, SUM(quantity) as on_hand')
                ->groupBy('inventory_item_id')
                ->pluck('on_hand', 'inventory_item_id');

            $summaryRows = $aggregates->map(function ($row) use ($beforeByFirstId, $afterByLastId, $items, $currentStock) {
                $item = $items->get($row->inventory_item_id);
                $opening = $beforeByFirstId->get($row->first_id);
                $closing = $afterByLastId->get($row->last_id);

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
        } else {
            $detailRows = (clone $base)
                ->with(['item.unit:id,symbol,name', 'branch:id,name', 'addedBy:id,name'])
                ->orderByDesc('consumption_date')
                ->orderByDesc('id')
                ->get();
        }

        $branchName = null;
        if ($branchFilter !== 'all' && $branchFilter !== '') {
            $branchName = optional(Branch::where('restaurant_id', restaurant()->id)->find($branchFilter))->name;
        }
        $itemName = null;
        if ($itemFilter) {
            $itemName = optional(InventoryItem::where('restaurant_id', restaurant()->id)->find($itemFilter))->name;
        }

        // ── Disposal data ────────────────────────────────────────────────────
        $disposalBase = InventoryDisposal::query()
            ->where('restaurant_id', restaurant()->id)
            ->whereDate('disposal_date', '>=', $startDate)
            ->whereDate('disposal_date', '<=', $endDate);

        if ($branchFilter !== 'all' && $branchFilter !== '') {
            $disposalBase->where('branch_id', $branchFilter);
        }
        if (!empty($itemFilter)) {
            $disposalBase->where('inventory_item_id', $itemFilter);
        }
        if (trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $disposalBase->whereHas('item', function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('item_code', 'like', $term);
            });
        }

        $disposalTotals = [
            'disposed' => (float) (clone $disposalBase)->sum('quantity'),
            'entries'  => (int)   (clone $disposalBase)->count(),
            'items'    => (int)   (clone $disposalBase)->distinct('inventory_item_id')->count('inventory_item_id'),
        ];

        $disposalSummaryRows = collect();
        $disposalDetailRows  = collect();

        if ($viewMode === 'summary') {
            $disposalAggregates = (clone $disposalBase)
                ->selectRaw('inventory_item_id,
                    SUM(quantity)      as total_disposed,
                    COUNT(*)           as entries,
                    MIN(disposal_date) as first_date,
                    MAX(disposal_date) as last_date')
                ->groupBy('inventory_item_id')
                ->orderByDesc('total_disposed')
                ->get();

            $disposalItemIds = $disposalAggregates->pluck('inventory_item_id')->all();
            $disposalItems   = InventoryItem::with('unit:id,symbol,name')
                ->whereIn('id', $disposalItemIds)
                ->get()
                ->keyBy('id');

            $disposalSummaryRows = $disposalAggregates->map(function ($row) use ($disposalItems) {
                $item = $disposalItems->get($row->inventory_item_id);
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
        } else {
            $disposalDetailRows = (clone $disposalBase)
                ->with(['item.unit:id,symbol,name', 'branch:id,name', 'addedBy:id,name'])
                ->orderByDesc('disposal_date')
                ->orderByDesc('id')
                ->get();
        }
        // ─────────────────────────────────────────────────────────────────────

        return view('inventory::stock.consumption-report-print', [
            'viewMode'            => $viewMode,
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'branchFilter'        => $branchFilter,
            'branchName'          => $branchName,
            'itemFilter'          => $itemFilter,
            'itemName'            => $itemName,
            'search'              => $search,
            'totals'              => $totals,
            'summaryRows'         => $summaryRows,
            'detailRows'          => $detailRows,
            'disposalTotals'      => $disposalTotals,
            'disposalSummaryRows' => $disposalSummaryRows,
            'disposalDetailRows'  => $disposalDetailRows,
        ]);
    }

    /**
     * Display the inventory disposal (wastage) list.
     */
    public function disposal()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Stock'), 403);
        return view('inventory::stock.disposal');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('inventory::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inventory::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('inventory::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
