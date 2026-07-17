<?php

namespace Modules\Inventory\Services;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\PurchaseLocation;

class ItemPurchasesReportService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function parseFiltersFromInput(array $input): array
    {
        $categoryId = $input['categoryFilter'] ?? $input['category_filter'] ?? null;
        $itemIds = $input['selectedItemIds'] ?? $input['item_ids'] ?? [];
        $itemCodes = $input['selectedItemCodes'] ?? $input['item_codes'] ?? [];

        if (! is_array($itemIds)) {
            $itemIds = $itemIds !== null && $itemIds !== '' ? [(int) $itemIds] : [];
        }

        if (! is_array($itemCodes)) {
            $itemCodes = $itemCodes !== null && $itemCodes !== '' ? [(string) $itemCodes] : [];
        }

        return [
            'start_date' => $input['startDate'] ?? $input['start_date'] ?? null,
            'end_date' => $input['endDate'] ?? $input['end_date'] ?? null,
            'branch_filter' => (string) ($input['branchFilter'] ?? $input['branch_filter'] ?? 'all'),
            'location_filter' => (string) ($input['locationFilter'] ?? $input['location_filter'] ?? 'all'),
            'category_filter' => ($categoryId === '' || $categoryId === 'all' || $categoryId === null)
                ? null
                : (int) $categoryId,
            'item_ids' => array_values(array_unique(array_map('intval', array_filter($itemIds)))),
            'item_codes' => array_values(array_unique(array_filter(array_map(
                fn ($code) => trim((string) $code),
                $itemCodes
            )))),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int>|null  null = all items in scope
     */
    public function resolveFilteredItemIds(int $restaurantId, array $filters): ?array
    {
        $fromItems = ! empty($filters['item_ids']) ? $filters['item_ids'] : null;

        $fromCodes = null;
        if (! empty($filters['item_codes'])) {
            $fromCodes = InventoryItem::query()
                ->where('restaurant_id', $restaurantId)
                ->whereIn('item_code', $filters['item_codes'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($fromItems !== null && $fromCodes !== null) {
            return array_values(array_intersect($fromItems, $fromCodes));
        }

        if ($fromItems !== null) {
            return $fromItems;
        }

        if ($fromCodes !== null) {
            return $fromCodes;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAggregatedRows(int $restaurantId, array $filters): Collection
    {
        $itemIds = $this->resolveFilteredItemIds($restaurantId, $filters);

        if ($itemIds === []) {
            return collect();
        }

        $quantityExpr = 'SUM(CASE WHEN purchase_order_items.received_quantity > 0 THEN purchase_order_items.received_quantity ELSE purchase_order_items.quantity END)';
        $subtotalExpr = 'SUM(COALESCE(purchase_order_items.subtotal, purchase_order_items.quantity * purchase_order_items.unit_price, 0))';

        $query = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('branches', 'branches.id', '=', 'purchase_orders.branch_id')
            ->leftJoin('purchase_locations', 'purchase_locations.id', '=', 'purchase_orders.location_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'purchase_order_items.inventory_item_id')
            ->where('branches.restaurant_id', $restaurantId)
            ->where('inventory_items.restaurant_id', $restaurantId)
            ->where('purchase_orders.status', 'received')
            ->select([
                'purchase_order_items.inventory_item_id',
                'purchase_orders.location_id',
                DB::raw("{$quantityExpr} as purchased_quantity"),
                DB::raw("{$subtotalExpr} as total_purchase_price"),
            ])
            ->groupBy('purchase_order_items.inventory_item_id', 'purchase_orders.location_id');

        $this->applyLocationFilter($query, 'purchase_orders.location_id', $filters);
        $this->applyBranchLocationFilter($query, 'purchase_locations.branch_id', $filters);
        $this->applyDateRange($query, 'purchase_orders.order_date', $filters);
        $this->applyCategoryFilter($query, $filters);

        if ($itemIds !== null) {
            $query->whereIn('purchase_order_items.inventory_item_id', $itemIds);
        }

        $rawRows = $query->get();

        if ($rawRows->isEmpty()) {
            return collect();
        }

        $itemIdsInRows = $rawRows->pluck('inventory_item_id')->unique()->values();
        $locationIds = $rawRows->pluck('location_id')->filter()->unique()->values();

        $items = InventoryItem::query()
            ->with(['unit:id,symbol,name', 'category:id,name'])
            ->where('restaurant_id', $restaurantId)
            ->whereIn('id', $itemIdsInRows)
            ->get()
            ->keyBy('id');

        $locations = PurchaseLocation::query()
            ->with('branch:id,name')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('id', $locationIds)
            ->get()
            ->keyBy('id');

        return $rawRows->map(function ($row) use ($items, $locations) {
            $item = $items->get((int) $row->inventory_item_id);
            $location = $locations->get((int) $row->location_id);

            return (object) [
                'item_id' => (int) $row->inventory_item_id,
                'item_name' => $item?->name ?? '--',
                'item_code' => $item?->item_code,
                'unit' => $item?->unit?->symbol ?? $item?->unit?->name ?? '--',
                'location' => $location?->name ?? '--',
                'location_type' => $location?->type ?? null,
                'location_id' => $row->location_id ? (int) $row->location_id : null,
                'branch' => $this->resolveLocationBranchLabel($location),
                'category' => $item?->category?->name ?? '--',
                'purchased_quantity' => (float) $row->purchased_quantity,
                'total_purchase_price' => (float) $row->total_purchase_price,
            ];
        })->sortBy(['location', 'item_name'])->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_purchased_quantity: float, total_purchase_amount: float, total_current_stock: float}
     */
    public function getSummaryTotals(int $restaurantId, array $filters, Collection $rows): array
    {
        return [
            'total_purchased_quantity' => (float) $rows->sum('purchased_quantity'),
            'total_purchase_amount' => (float) $rows->sum('total_purchase_price'),
            'total_current_stock' => $this->getCurrentStockTotal($restaurantId, $filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getCurrentStockTotal(int $restaurantId, array $filters): float
    {
        $itemIds = $this->resolveFilteredItemIds($restaurantId, $filters);

        if ($itemIds === []) {
            return 0.0;
        }

        $query = InventoryStock::query()
            ->whereHas('inventoryItem', function ($itemQuery) use ($restaurantId, $filters) {
                $itemQuery->where('restaurant_id', $restaurantId);
                $this->applyCategoryFilterOnItemQuery($itemQuery, $filters);
            });

        if ($itemIds !== null) {
            $query->whereIn('inventory_item_id', $itemIds);
        }

        $this->applyLocationFilter($query, 'location_id', $filters);
        $this->applyBranchStockFilter($query, $filters);

        return (float) $query->sum('quantity');
    }

    public function resolveBranchLabel(int $restaurantId, string $branchFilter): string
    {
        if ($branchFilter === '' || $branchFilter === 'all') {
            return trans('app.all');
        }

        return Branch::query()
            ->where('restaurant_id', $restaurantId)
            ->find((int) $branchFilter)?->name ?? trans('app.all');
    }

    public function resolveLocationLabel(int $restaurantId, string $locationFilter): string
    {
        if ($locationFilter === '' || $locationFilter === 'all') {
            return trans('app.all');
        }

        $location = PurchaseLocation::query()
            ->with('branch:id,name')
            ->where('restaurant_id', $restaurantId)
            ->find((int) $locationFilter);

        return $location?->display_name ?? trans('app.all');
    }

    public function resolveCategoryLabel(int $restaurantId, ?int $categoryId): string
    {
        if (! $categoryId) {
            return trans('app.all');
        }

        return InventoryItemCategory::query()
            ->where('restaurant_id', $restaurantId)
            ->find($categoryId)?->name ?? trans('app.all');
    }

    public function formatQuantity(float $qty): string
    {
        $formatted = number_format($qty, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    /**
     * @return Collection<int, PurchaseLocation>
     */
    public function getFilterLocations(int $restaurantId, string $branchFilter = 'all'): Collection
    {
        $query = PurchaseLocation::query()
            ->with('branch:id,name')
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name');

        if ($branchFilter !== '' && $branchFilter !== 'all') {
            $branchId = (int) $branchFilter;
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhere('type', 'warehouse');
            });
        }

        return $query->get(['id', 'name', 'type', 'branch_id']);
    }

    protected function resolveLocationBranchLabel(?PurchaseLocation $location): string
    {
        if (! $location) {
            return '--';
        }

        if ($location->type === 'branch' && $location->branch) {
            return $location->branch->name;
        }

        return trans('inventory::modules.reports.item_purchases.table.warehouse');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyLocationFilter($query, string $column, array $filters): void
    {
        $location = $filters['location_filter'] ?? 'all';

        if ($location !== '' && $location !== 'all') {
            $query->where($column, (int) $location);
        }
    }

    /**
     * Filters by the branch linked to the purchase delivery location.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyBranchLocationFilter($query, string $column, array $filters): void
    {
        $branch = $filters['branch_filter'] ?? 'all';

        if ($branch !== '' && $branch !== 'all') {
            $query->where($column, (int) $branch);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyBranchStockFilter($query, array $filters): void
    {
        $branch = $filters['branch_filter'] ?? 'all';
        $location = $filters['location_filter'] ?? 'all';

        if ($location !== '' && $location !== 'all') {
            return;
        }

        if ($branch === '' || $branch === 'all') {
            return;
        }

        $branchId = (int) $branch;
        $locationIds = PurchaseLocation::query()
            ->where('branch_id', $branchId)
            ->pluck('id');

        if ($locationIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('location_id', $locationIds);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyDateRange($query, string $column, array $filters): void
    {
        if (! empty($filters['start_date'])) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyCategoryFilter($query, array $filters): void
    {
        if (empty($filters['category_filter'])) {
            return;
        }

        $query->where('inventory_items.inventory_item_category_id', (int) $filters['category_filter']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyCategoryFilterOnItemQuery($query, array $filters): void
    {
        if (empty($filters['category_filter'])) {
            return;
        }

        $query->where('inventory_item_category_id', (int) $filters['category_filter']);
    }
}
