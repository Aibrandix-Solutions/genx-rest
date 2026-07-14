<?php

namespace Modules\Inventory\Services;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Inventory\Entities\InventoryTransfer;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\PurchaseLocation;

class TransferReportService
{
    public const REPORT_TYPES = ['item', 'location', 'daily', 'monthly'];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function parseFiltersFromInput(array $input): array
    {
        $itemIds = $input['selectedItemIds'] ?? $input['item_ids'] ?? [];

        if (! is_array($itemIds)) {
            $itemIds = $itemIds !== null && $itemIds !== '' ? [(int) $itemIds] : [];
        }

        $startDate = $input['startDate'] ?? $input['start_date'] ?? null;
        $endDate = $input['endDate'] ?? $input['end_date'] ?? null;

        return [
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
            'branch_filter' => (string) ($input['branchFilter'] ?? $input['branch_filter'] ?? 'all'),
            'from_location_filter' => (string) ($input['fromLocationFilter'] ?? $input['from_location_filter'] ?? 'all'),
            'to_location_filter' => (string) ($input['toLocationFilter'] ?? $input['to_location_filter'] ?? 'all'),
            'search' => trim((string) ($input['search'] ?? '')),
            'item_ids' => array_values(array_unique(array_map('intval', array_filter($itemIds)))),
            'report_type' => $this->normalizeReportType($input['reportType'] ?? $input['report_type'] ?? 'item'),
        ];
    }

    public function normalizeReportType(?string $type): string
    {
        $type = (string) $type;

        return in_array($type, self::REPORT_TYPES, true) ? $type : 'item';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRows(int $restaurantId, array $filters): Collection
    {
        return match ($filters['report_type'] ?? 'item') {
            'location' => $this->getItemLineRows($restaurantId, $filters, applyLocationFilters: true),
            'daily' => $this->getDailyRows($restaurantId, $filters),
            'monthly' => $this->getMonthlyRows($restaurantId, $filters),
            default => $this->getItemLineRows($restaurantId, $filters, applyLocationFilters: false),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_quantity: float, total_cost: float, total_transfers: int, total_lines: int}
     */
    public function getSummaryTotals(Collection $rows, string $reportType): array
    {
        if ($reportType === 'monthly') {
            return [
                'total_quantity' => (float) $rows->sum('total_quantity'),
                'total_cost' => (float) $rows->sum('total_cost'),
                'total_transfers' => (int) $rows->sum('transfers_count'),
                'total_lines' => (int) $rows->sum('lines_count'),
            ];
        }

        if ($reportType === 'daily') {
            return [
                'total_quantity' => (float) $rows->sum('total_quantity'),
                'total_cost' => (float) $rows->sum('total_cost'),
                'total_transfers' => $rows->count(),
                'total_lines' => (int) $rows->sum('items_count'),
            ];
        }

        return [
            'total_quantity' => (float) $rows->sum('quantity'),
            'total_cost' => (float) $rows->sum('total_cost'),
            'total_transfers' => $rows->pluck('transfer_id')->unique()->count(),
            'total_lines' => $rows->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getItemLineRows(int $restaurantId, array $filters, bool $applyLocationFilters = false): Collection
    {
        $query = InventoryTransferItem::query()
            ->whereHas('transfer', function (Builder $q) use ($restaurantId, $filters, $applyLocationFilters) {
                $this->applyCompletedTransferScope($q, $restaurantId, $filters, $applyLocationFilters);
            })
            ->with([
                'transfer.sourceLocation',
                'transfer.destinationLocation',
                'transfer.sourceBranch',
                'transfer.destinationBranch',
                'sourceItem.category',
                'sourceItem.unit',
                'unit',
            ]);

        if (! empty($filters['item_ids'])) {
            $query->whereIn('source_inventory_item_id', $filters['item_ids']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('sourceItem', function (Builder $itemQuery) use ($search) {
                    $itemQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('item_code', 'like', '%' . $search . '%');
                })->orWhereHas('transfer', function (Builder $transferQuery) use ($search) {
                    $transferQuery->where('transfer_number', 'like', '%' . $search . '%');
                });
            });
        }

        return $query->get()
            ->map(fn (InventoryTransferItem $item) => $this->mapItemLineRow($item))
            ->sortByDesc(fn ($row) => $row->confirmed_at?->timestamp ?? 0)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getDailyRows(int $restaurantId, array $filters): Collection
    {
        $query = InventoryTransfer::query()
            ->with([
                'sourceLocation',
                'destinationLocation',
                'sourceBranch',
                'destinationBranch',
                'items.sourceItem',
            ]);

        $this->applyCompletedTransferScope($query, $restaurantId, $filters, applyLocationFilters: false);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('transfer_number', 'like', '%' . $search . '%');
        }

        if (! empty($filters['item_ids'])) {
            $query->whereHas('items', function (Builder $q) use ($filters) {
                $q->whereIn('source_inventory_item_id', $filters['item_ids']);
            });
        }

        return $query->orderByDesc('confirmed_at')
            ->get()
            ->map(function (InventoryTransfer $transfer) {
                $totalQuantity = (float) $transfer->items->sum('confirmed_quantity');
                $totalCost = (float) $transfer->items->sum(function (InventoryTransferItem $item) {
                    $qty = (float) ($item->confirmed_quantity ?? 0);
                    $unitCost = (float) ($item->sourceItem?->unit_purchase_price ?? 0);

                    return $qty * $unitCost;
                });

                return (object) [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'confirmed_at' => $transfer->confirmed_at,
                    'date_label' => $transfer->confirmed_at
                        ? $transfer->confirmed_at->timezone(timezone())->translatedFormat('M d, Y')
                        : '—',
                    'from_location' => $this->resolveLocationLabel($transfer, 'source'),
                    'to_location' => $this->resolveLocationLabel($transfer, 'destination'),
                    'items_count' => $transfer->items->count(),
                    'total_quantity' => $totalQuantity,
                    'total_cost' => $totalCost,
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMonthlyRows(int $restaurantId, array $filters): Collection
    {
        $query = InventoryTransfer::query()
            ->with(['items.sourceItem']);

        $this->applyCompletedTransferScope($query, $restaurantId, $filters, applyLocationFilters: false);

        if (! empty($filters['item_ids'])) {
            $query->whereHas('items', function (Builder $q) use ($filters) {
                $q->whereIn('source_inventory_item_id', $filters['item_ids']);
            });
        }

        $transfers = $query->get();

        return $transfers
            ->groupBy(function (InventoryTransfer $transfer) {
                if (! $transfer->confirmed_at) {
                    return 'unknown';
                }

                return $transfer->confirmed_at->timezone(timezone())->format('Y-m');
            })
            ->map(function (Collection $group, string $monthKey) {
                $first = $group->first();
                $monthLabel = $monthKey === 'unknown' || ! $first?->confirmed_at
                    ? '—'
                    : $first->confirmed_at->timezone(timezone())->translatedFormat('F Y');

                $linesCount = 0;
                $totalQuantity = 0.0;
                $totalCost = 0.0;

                foreach ($group as $transfer) {
                    foreach ($transfer->items as $item) {
                        $linesCount++;
                        $qty = (float) ($item->confirmed_quantity ?? 0);
                        $unitCost = (float) ($item->sourceItem?->unit_purchase_price ?? 0);
                        $totalQuantity += $qty;
                        $totalCost += $qty * $unitCost;
                    }
                }

                return (object) [
                    'month_key' => $monthKey,
                    'month_label' => $monthLabel,
                    'transfers_count' => $group->count(),
                    'lines_count' => $linesCount,
                    'total_quantity' => $totalQuantity,
                    'total_cost' => $totalCost,
                ];
            })
            ->sortByDesc('month_key')
            ->values();
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

    public function resolveLocationFilterLabel(int $restaurantId, string $locationFilter): string
    {
        if ($locationFilter === '' || $locationFilter === 'all') {
            return trans('app.all');
        }

        return PurchaseLocation::query()
            ->where('restaurant_id', $restaurantId)
            ->find((int) $locationFilter)?->name ?? trans('app.all');
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

    public function formatQuantity(float $qty): string
    {
        $formatted = number_format($qty, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    public function defaultStartDate(): string
    {
        return Carbon::now()->startOfMonth()->format('Y-m-d');
    }

    public function defaultEndDate(): string
    {
        return Carbon::now()->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyCompletedTransferScope(
        Builder $query,
        int $restaurantId,
        array $filters,
        bool $applyLocationFilters
    ): void {
        $query->where('restaurant_id', $restaurantId)
            ->where('status', 'completed');

        if (! empty($filters['start_date'])) {
            $query->whereDate('confirmed_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('confirmed_at', '<=', $filters['end_date']);
        }

        $branchFilter = $filters['branch_filter'] ?? 'all';
        if ($branchFilter !== '' && $branchFilter !== 'all') {
            $branchId = (int) $branchFilter;
            $query->where(function (Builder $q) use ($branchId) {
                $q->where('source_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId)
                    ->orWhereHas('sourceLocation', fn (Builder $sq) => $sq->where('branch_id', $branchId))
                    ->orWhereHas('destinationLocation', fn (Builder $sq) => $sq->where('branch_id', $branchId));
            });
        }

        if ($applyLocationFilters) {
            $fromLocation = $filters['from_location_filter'] ?? 'all';
            if ($fromLocation !== '' && $fromLocation !== 'all') {
                $query->where('source_location_id', (int) $fromLocation);
            }

            $toLocation = $filters['to_location_filter'] ?? 'all';
            if ($toLocation !== '' && $toLocation !== 'all') {
                $query->where('destination_location_id', (int) $toLocation);
            }
        }
    }

    protected function mapItemLineRow(InventoryTransferItem $item): object
    {
        $transfer = $item->transfer;
        $sourceItem = $item->sourceItem;
        $quantity = (float) ($item->confirmed_quantity ?? 0);
        $unitCost = (float) ($sourceItem?->unit_purchase_price ?? 0);

        return (object) [
            'transfer_item_id' => $item->id,
            'transfer_id' => $transfer?->id,
            'transfer_number' => $transfer?->transfer_number ?? '—',
            'item_id' => $item->source_inventory_item_id,
            'product_code' => $sourceItem?->item_code ?: '—',
            'product_name' => $sourceItem?->name ?? '—',
            'category' => $sourceItem?->category?->name ?? '—',
            'from_location' => $transfer ? $this->resolveLocationLabel($transfer, 'source') : '—',
            'to_location' => $transfer ? $this->resolveLocationLabel($transfer, 'destination') : '—',
            'unit' => $item->unit?->symbol ?? $sourceItem?->unit?->symbol ?? $item->unit?->name ?? '—',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'confirmed_at' => $transfer?->confirmed_at,
            'date_label' => $transfer?->confirmed_at
                ? $transfer->confirmed_at->timezone(timezone())->translatedFormat('M d, Y')
                : '—',
        ];
    }

    protected function resolveLocationLabel(InventoryTransfer $transfer, string $side): string
    {
        if ($side === 'source') {
            if ($transfer->sourceLocation) {
                return $transfer->sourceLocation->name;
            }

            return $transfer->sourceBranch?->name ?? '—';
        }

        if ($transfer->destinationLocation) {
            return $transfer->destinationLocation->name;
        }

        return $transfer->destinationBranch?->name ?? '—';
    }
}
