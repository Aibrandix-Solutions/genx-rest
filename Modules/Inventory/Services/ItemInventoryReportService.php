<?php

namespace Modules\Inventory\Services;

use App\Models\Branch;
use App\Scopes\BranchScope;
use Illuminate\Support\Collection;
use Modules\Inventory\Entities\InventoryConsumption;
use Modules\Inventory\Entities\InventoryDisposal;
use Modules\Inventory\Entities\InventoryTransferItem;
use Modules\Inventory\Entities\PurchaseLocation;
use Modules\Inventory\Entities\PurchaseOrderItem;

class ItemInventoryReportService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function parseFiltersFromInput(array $input): array
    {
        $itemId = $input['itemFilter'] ?? $input['item_id'] ?? null;
        $locationId = $input['locationId'] ?? $input['location_id'] ?? null;

        return [
            'start_date' => $input['startDate'] ?? $input['start_date'] ?? null,
            'end_date' => $input['endDate'] ?? $input['end_date'] ?? null,
            'branch_filter' => (string) ($input['branchFilter'] ?? $input['branch_filter'] ?? 'all'),
            'supplier_id' => is_numeric($input['supplierId'] ?? $input['supplier_id'] ?? null)
                ? (int) ($input['supplierId'] ?? $input['supplier_id'])
                : null,
            'purchase_status' => (string) ($input['purchaseStatus'] ?? $input['purchase_status'] ?? ''),
            'payment_status' => (string) ($input['paymentStatus'] ?? $input['payment_status'] ?? ''),
            'search' => trim((string) ($input['search'] ?? '')),
            'item_id' => is_numeric($itemId) ? (int) $itemId : null,
            'activity_type' => (string) ($input['activityType'] ?? $input['activity_type'] ?? 'all'),
            'location_id' => is_numeric($locationId) ? (int) $locationId : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPurchaseRows(int $restaurantId, array $filters): Collection
    {
        $query = PurchaseOrderItem::query()
            ->with([
                'inventoryItem.unit:id,symbol,name',
                'purchaseOrder' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
                'purchaseOrder.supplier:id,name',
                'purchaseOrder.branch:id,name',
                'purchaseOrder.location:id,name',
                'purchaseOrder.payments:id,purchase_order_id,amount',
            ])
            ->whereHas('purchaseOrder', function ($po) use ($restaurantId, $filters) {
                $po->withoutGlobalScope(BranchScope::class)
                    ->whereHas('branch', fn ($b) => $b->where('restaurant_id', $restaurantId));

                $this->applyBranchFilterOnColumn($po, 'branch_id', $filters);
                $this->applyDateRange($po, 'order_date', $filters);

                if (! empty($filters['supplier_id'])) {
                    $po->where('supplier_id', (int) $filters['supplier_id']);
                }

                if (! empty($filters['purchase_status'])) {
                    $po->where('status', $filters['purchase_status']);
                }

                $this->applyPaymentStatusFilter($po, $filters['payment_status'] ?? '');
                $this->applyLocationFilter($po, $filters, 'location_id');
            });

        $this->applyItemFilters($query, $filters, 'inventory_item_id', 'inventoryItem');

        $query
            ->join('purchase_orders as po_sort', 'po_sort.id', '=', 'purchase_order_items.purchase_order_id')
            ->orderByDesc('po_sort.order_date')
            ->orderByDesc('purchase_order_items.id')
            ->select('purchase_order_items.*');

        return $query->get()->map(function (PurchaseOrderItem $line) {
            $po = $line->purchaseOrder;
            $item = $line->inventoryItem;
            $location = $po?->location;

            return (object) [
                'item_id' => (int) $line->inventory_item_id,
                'date' => $po?->order_date,
                'po_number' => $po?->po_number ?? '--',
                'invoice_no' => $po?->invoice_no ?? '--',
                'branch' => $po?->branch?->name ?? '--',
                'branch_id' => (int) ($po?->branch_id ?? 0),
                'supplier' => $po?->supplier?->name ?? '--',
                'location' => $location?->name ?? '--',
                'item_name' => $item?->name ?? '--',
                'item_code' => $item?->item_code,
                'quantity' => (float) $line->quantity,
                'received_quantity' => (float) ($line->received_quantity ?? 0),
                'unit' => $item?->unit?->symbol ?? $item?->unit?->name ?? '',
                'unit_price' => (float) $line->unit_price,
                'subtotal' => (float) ($line->subtotal ?? ($line->quantity * $line->unit_price)),
                'purchase_status' => $po?->status ?? '--',
                'payment_status' => $po?->payment_status ?? '--',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getUsageRows(int $restaurantId, array $filters): Collection
    {
        $query = InventoryConsumption::query()
            ->with(['item.unit:id,symbol,name', 'branch:id,name', 'location:id,name', 'addedBy:id,name', 'menuItems:id'])
            ->where('restaurant_id', $restaurantId);

        $this->applyBranchFilterOnColumn($query, 'branch_id', $filters);
        $this->applyDateRange($query, 'consumption_date', $filters);
        $this->applyItemFilters($query, $filters, 'inventory_item_id', 'item');
        $this->applyLocationFilter($query, $filters, 'location_id');

        return $query
            ->orderByDesc('consumption_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryConsumption $row) => (object) [
                'item_id' => (int) $row->inventory_item_id,
                'date' => $row->consumption_date,
                'branch' => $row->branch?->name ?? '--',
                'branch_id' => (int) ($row->branch_id ?? 0),
                'item_name' => $row->item?->name ?? '--',
                'item_code' => $row->item?->item_code,
                'quantity' => (float) $row->quantity,
                'unit' => $row->item?->unit?->symbol ?? $row->item?->unit?->name ?? '',
                'location' => $row->location?->name ?? '--',
                'menus_used' => $row->menuItems
                    ->map(fn ($menuItem) => $menuItem->item_name)
                    ->filter()
                    ->implode(', ') ?: '--',
                'note' => $row->note,
                'recorded_by' => $row->addedBy?->name ?? '--',
            ]);
    }

    /**
     * Branch-to-branch transfer line items.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getMovementRows(int $restaurantId, array $filters): Collection
    {
        $query = InventoryTransferItem::query()
            ->with([
                'transfer.sourceBranch:id,name',
                'transfer.destinationBranch:id,name',
                'transfer.sourceLocation:id,name',
                'transfer.destinationLocation:id,name',
                'sourceItem.unit:id,symbol,name',
                'destinationItem:id,name,item_code',
            ])
            ->whereHas('transfer', function ($transfer) use ($restaurantId, $filters) {
                $transfer->where('restaurant_id', $restaurantId)
                    ->whereRaw('source_branch_id != destination_branch_id');

                $branch = $filters['branch_filter'] ?? 'all';
                if ($branch !== '' && $branch !== 'all') {
                    $branchId = (int) $branch;
                    $transfer->where(function ($q) use ($branchId) {
                        $q->where('source_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                }

                if (! empty($filters['start_date'])) {
                    $transfer->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (! empty($filters['end_date'])) {
                    $transfer->whereDate('created_at', '<=', $filters['end_date']);
                }

                $this->applyTransferLocationFilter($transfer, $filters);
            });

        $this->applyItemFilters($query, $filters, 'source_inventory_item_id', 'sourceItem');

        return $query
            ->join('inventory_transfers as tr_sort', 'tr_sort.id', '=', 'inventory_transfer_items.inventory_transfer_id')
            ->orderByDesc('tr_sort.created_at')
            ->orderByDesc('inventory_transfer_items.id')
            ->select('inventory_transfer_items.*')
            ->get()
            ->map(fn (InventoryTransferItem $line) => (object) [
                'item_id' => (int) $line->source_inventory_item_id,
                'date' => $line->transfer?->created_at,
                'transfer_number' => $line->transfer?->transfer_number ?? '--',
                'from_branch' => $line->transfer?->sourceBranch?->name ?? '--',
                'from_branch_id' => (int) ($line->transfer?->source_branch_id ?? 0),
                'to_branch' => $line->transfer?->destinationBranch?->name ?? '--',
                'to_branch_id' => (int) ($line->transfer?->destination_branch_id ?? 0),
                'from_location' => $line->transfer?->sourceLocation?->name ?? '--',
                'to_location' => $line->transfer?->destinationLocation?->name ?? '--',
                'item_name' => $line->sourceItem?->name ?? '--',
                'item_code' => $line->sourceItem?->item_code,
                'quantity_out' => (float) $line->requested_quantity,
                'quantity_in' => (float) ($line->confirmed_quantity ?? 0),
                'unit' => $line->sourceItem?->unit?->symbol ?? $line->sourceItem?->unit?->name ?? '',
                'status' => $line->status ?? $line->transfer?->status ?? '--',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getWastageRows(int $restaurantId, array $filters): Collection
    {
        $query = InventoryDisposal::query()
            ->with(['item.unit:id,symbol,name', 'branch:id,name', 'location:id,name', 'addedBy:id,name'])
            ->where('restaurant_id', $restaurantId);

        $this->applyBranchFilterOnColumn($query, 'branch_id', $filters);
        $this->applyDateRange($query, 'disposal_date', $filters);
        $this->applyItemFilters($query, $filters, 'inventory_item_id', 'item');
        $this->applyLocationFilter($query, $filters, 'location_id');

        return $query
            ->orderByDesc('disposal_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryDisposal $row) => (object) [
                'item_id' => (int) $row->inventory_item_id,
                'date' => $row->disposal_date,
                'branch' => $row->branch?->name ?? '--',
                'branch_id' => (int) ($row->branch_id ?? 0),
                'item_name' => $row->item?->name ?? '--',
                'item_code' => $row->item?->item_code,
                'quantity' => (float) $row->quantity,
                'unit' => $row->item?->unit?->symbol ?? $row->item?->unit?->name ?? '',
                'location' => $row->location?->name ?? '--',
                'reason' => $row->reason ?? '--',
                'recorded_by' => $row->addedBy?->name ?? '--',
            ]);
    }

    /**
     * @return array{
     *     purchases: Collection,
     *     usage: Collection,
     *     movements: Collection,
     *     wastage: Collection,
     * }
     */
    public function getAllSections(int $restaurantId, array $filters): array
    {
        return [
            'purchases' => $this->getPurchaseRows($restaurantId, $filters),
            'usage' => $this->getUsageRows($restaurantId, $filters),
            'movements' => $this->getMovementRows($restaurantId, $filters),
            'wastage' => $this->getWastageRows($restaurantId, $filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{purchases: Collection, usage: Collection, movements: Collection, wastage: Collection}
     */
    public function getItemDetailSections(int $restaurantId, array $filters, int $itemId): array
    {
        $detailFilters = $filters;
        $detailFilters['item_id'] = $itemId;

        return $this->getAllSections($restaurantId, $detailFilters);
    }

    /**
     * Build chronological item activity ledger rows with running balance.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object{
     *     date: mixed,
     *     ref_no: string,
     *     transaction_type: string,
     *     location: string,
     *     qty_in: float,
     *     qty_out: float,
     *     running_balance: float,
     *     unit_cost: ?float,
     *     value: ?float
     * }>
     */
    public function getItemLedgerRows(int $restaurantId, array $filters, int $itemId): Collection
    {
        $sections = $this->getItemDetailSections(
            $restaurantId,
            $this->relaxedItemLedgerFilters($itemId, $filters),
            $itemId
        );
        $rows = collect();

        foreach ($sections['purchases'] as $row) {
            $qtyIn = (float) ($row->received_quantity ?? 0);
            $unitCost = (float) ($row->unit_price ?? 0);
            $rows->push((object) [
                'date' => $row->date,
                'ref_no' => (string) ($row->po_number ?? '--'),
                'transaction_type' => 'Purchase',
                'branch_id' => (int) ($row->branch_id ?? 0),
                'location' => (string) ($row->location ?? '--'),
                'qty_in' => $qtyIn,
                'qty_out' => 0.0,
                'running_balance' => 0.0,
                'unit_cost' => $unitCost > 0 ? $unitCost : null,
                'value' => (float) ($row->subtotal ?? ($qtyIn * $unitCost)),
            ]);
        }

        foreach ($sections['usage'] as $row) {
            $rows->push((object) [
                'date' => $row->date,
                'ref_no' => 'USE-' . ($row->item_id ?? $itemId),
                'transaction_type' => 'Menu Usage',
                'branch_id' => (int) ($row->branch_id ?? 0),
                'location' => trim(($row->branch ?? '--') . ' ' . ($row->location ?? '')),
                'qty_in' => 0.0,
                'qty_out' => (float) ($row->quantity ?? 0),
                'running_balance' => 0.0,
                'unit_cost' => null,
                'value' => null,
            ]);
        }

        foreach ($sections['movements'] as $row) {
            $date = $row->date;
            $ref = (string) ($row->transfer_number ?? '--');
            $qtyOut = (float) ($row->quantity_out ?? 0);
            $qtyIn = (float) ($row->quantity_in ?? 0);

            if ($qtyOut > 0) {
                $rows->push((object) [
                    'date' => $date,
                    'ref_no' => $ref,
                    'transaction_type' => 'Transfer Out',
                    'branch_id' => (int) ($row->from_branch_id ?? 0),
                    'location' => trim(($row->from_branch ?? '--') . ' ' . ($row->from_location ?? '')),
                    'qty_in' => 0.0,
                    'qty_out' => $qtyOut,
                    'running_balance' => 0.0,
                    'unit_cost' => null,
                    'value' => null,
                ]);
            }

            if ($qtyIn > 0) {
                $rows->push((object) [
                    'date' => $date,
                    'ref_no' => $ref,
                    'transaction_type' => 'Transfer In',
                    'branch_id' => (int) ($row->to_branch_id ?? 0),
                    'location' => trim(($row->to_branch ?? '--') . ' ' . ($row->to_location ?? '')),
                    'qty_in' => $qtyIn,
                    'qty_out' => 0.0,
                    'running_balance' => 0.0,
                    'unit_cost' => null,
                    'value' => null,
                ]);
            }
        }

        foreach ($sections['wastage'] as $row) {
            $rows->push((object) [
                'date' => $row->date,
                'ref_no' => 'WST-' . ($row->item_id ?? $itemId),
                'transaction_type' => 'Wastage',
                'branch_id' => (int) ($row->branch_id ?? 0),
                'location' => trim(($row->branch ?? '--') . ' ' . ($row->location ?? '')),
                'qty_in' => 0.0,
                'qty_out' => (float) ($row->quantity ?? 0),
                'running_balance' => 0.0,
                'unit_cost' => null,
                'value' => null,
            ]);
        }

        return $this->finalizeLedgerRows($rows);
    }

    /**
     * @param  array<string, mixed>  $ledgerFilters
     */
    public function filterLedgerRows(Collection $rows, array $ledgerFilters): Collection
    {
        $branchId = $ledgerFilters['branch_id'] ?? null;
        $startDate = trim((string) ($ledgerFilters['start_date'] ?? ''));
        $endDate = trim((string) ($ledgerFilters['end_date'] ?? ''));

        $matchesBranch = fn ($row) => ! $branchId || (int) ($row->branch_id ?? 0) === (int) $branchId;

        $matchesDate = function ($row) use ($startDate, $endDate): bool {
            if ($startDate === '' && $endDate === '') {
                return true;
            }

            $date = $this->ledgerRowDate($row);

            if ($startDate !== '' && $date < $startDate) {
                return false;
            }

            if ($endDate !== '' && $date > $endDate) {
                return false;
            }

            return true;
        };

        $visible = $rows->filter(fn ($row) => $matchesBranch($row) && $matchesDate($row));

        $openingBalance = 0.0;

        if ($startDate !== '') {
            $openingBalance = (float) $rows
                ->filter(function ($row) use ($matchesBranch, $startDate) {
                    if (! $matchesBranch($row)) {
                        return false;
                    }

                    return $this->ledgerRowDate($row) < $startDate;
                })
                ->sum(fn ($row) => (float) $row->qty_in - (float) $row->qty_out);
        }

        return $this->finalizeLedgerRows($visible, $openingBalance);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function relaxedItemLedgerFilters(int $itemId, array $filters): array
    {
        return array_merge($filters, [
            'item_id' => $itemId,
            'start_date' => null,
            'end_date' => null,
            'branch_filter' => 'all',
            'supplier_id' => null,
            'purchase_status' => '',
            'payment_status' => '',
            'location_id' => null,
            'search' => '',
        ]);
    }

    protected function ledgerRowDate(object $row): string
    {
        if ($row->date instanceof \DateTimeInterface) {
            return $row->date->format('Y-m-d');
        }

        return substr((string) $row->date, 0, 10);
    }

    protected function finalizeLedgerRows(Collection $rows, float $openingBalance = 0.0): Collection
    {
        $sorted = $rows->sortBy(function ($row) {
            $timestamp = $row->date instanceof \DateTimeInterface
                ? $row->date->getTimestamp()
                : strtotime((string) $row->date);

            return ($timestamp ?: 0) . '|' . $row->ref_no . '|' . $row->transaction_type;
        })->values();

        $running = $openingBalance;

        $withBalance = $sorted->map(function ($row) use (&$running) {
            $running += (float) $row->qty_in;
            $running -= (float) $row->qty_out;
            $row->running_balance = $running;

            return $row;
        });

        return $withBalance
            ->sortByDesc(function ($row) {
                $timestamp = $row->date instanceof \DateTimeInterface
                    ? $row->date->getTimestamp()
                    : strtotime((string) $row->date);

                return ($timestamp ?: 0) . '|' . $row->ref_no . '|' . $row->transaction_type;
            })
            ->values();
    }

    /**
     * One row per inventory item with aggregated Purchases / Usage / Movements / Wastages cells.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object{
     *     item_id: int,
     *     item_name: string,
     *     item_code: ?string,
     *     purchases: string,
     *     usage: string,
     *     movements: string,
     *     wastages: string,
     * }>
     */
    public function getItemSummaryRows(int $restaurantId, array $filters): Collection
    {
        $purchases = $this->getPurchaseRows($restaurantId, $filters);
        $usage = $this->getUsageRows($restaurantId, $filters);
        $movements = $this->getMovementRows($restaurantId, $filters);
        $wastage = $this->getWastageRows($restaurantId, $filters);

        /** @var array<int, array{item_name: string, item_code: ?string, unit: string, purchases: array{received: float, lines: int, subtotal: float}, usage: array{qty: float, entries: int}, movements: array{out: float, in: float, transfers: int}, wastages: array{qty: float, entries: int}}> $aggregates */
        $aggregates = [];

        $ensureItem = function (int $itemId, string $name, ?string $code, string $unit) use (&$aggregates): void {
            if (! isset($aggregates[$itemId])) {
                $aggregates[$itemId] = [
                    'item_name' => $name,
                    'item_code' => $code,
                    'unit' => $unit,
                    'purchases' => ['received' => 0.0, 'lines' => 0, 'subtotal' => 0.0],
                    'usage' => ['qty' => 0.0, 'entries' => 0],
                    'movements' => ['out' => 0.0, 'in' => 0.0, 'transfers' => 0],
                    'wastages' => ['qty' => 0.0, 'entries' => 0],
                ];

                return;
            }

            if ($aggregates[$itemId]['item_name'] === '--' && $name !== '--') {
                $aggregates[$itemId]['item_name'] = $name;
            }

            if (empty($aggregates[$itemId]['item_code']) && $code) {
                $aggregates[$itemId]['item_code'] = $code;
            }

            if ($aggregates[$itemId]['unit'] === '' && $unit !== '') {
                $aggregates[$itemId]['unit'] = $unit;
            }
        };

        foreach ($purchases as $row) {
            $itemId = (int) $row->item_id;
            $ensureItem($itemId, (string) $row->item_name, $row->item_code, (string) $row->unit);
            $aggregates[$itemId]['purchases']['received'] += (float) $row->received_quantity;
            $aggregates[$itemId]['purchases']['lines']++;
            $aggregates[$itemId]['purchases']['subtotal'] += (float) $row->subtotal;
        }

        foreach ($usage as $row) {
            $itemId = (int) $row->item_id;
            $ensureItem($itemId, (string) $row->item_name, $row->item_code, (string) $row->unit);
            $aggregates[$itemId]['usage']['qty'] += (float) $row->quantity;
            $aggregates[$itemId]['usage']['entries']++;
        }

        foreach ($movements as $row) {
            $itemId = (int) $row->item_id;
            $ensureItem($itemId, (string) $row->item_name, $row->item_code, (string) $row->unit);
            $aggregates[$itemId]['movements']['out'] += (float) $row->quantity_out;
            $aggregates[$itemId]['movements']['in'] += (float) $row->quantity_in;
            $aggregates[$itemId]['movements']['transfers']++;
        }

        foreach ($wastage as $row) {
            $itemId = (int) $row->item_id;
            $ensureItem($itemId, (string) $row->item_name, $row->item_code, (string) $row->unit);
            $aggregates[$itemId]['wastages']['qty'] += (float) $row->quantity;
            $aggregates[$itemId]['wastages']['entries']++;
        }

        $itemIds = collect(array_keys($aggregates));

        if (! empty($filters['item_id'])) {
            $selectedId = (int) $filters['item_id'];
            $itemIds = $itemIds->push($selectedId)->unique()->values();

            if (! isset($aggregates[$selectedId])) {
                $item = \Modules\Inventory\Entities\InventoryItem::query()
                    ->with('unit:id,symbol,name')
                    ->where('restaurant_id', $restaurantId)
                    ->find($selectedId);

                $aggregates[$selectedId] = [
                    'item_name' => $item?->name ?? '--',
                    'item_code' => $item?->item_code,
                    'unit' => $item?->unit?->symbol ?? $item?->unit?->name ?? '',
                    'purchases' => ['received' => 0.0, 'lines' => 0, 'subtotal' => 0.0],
                    'usage' => ['qty' => 0.0, 'entries' => 0],
                    'movements' => ['out' => 0.0, 'in' => 0.0, 'transfers' => 0],
                    'wastages' => ['qty' => 0.0, 'entries' => 0],
                ];
            }
        }

        $currencyId = restaurant()->currency_id ?? null;

        return $itemIds
            ->map(function (int $itemId) use ($aggregates, $currencyId) {
                $data = $aggregates[$itemId];

                return (object) [
                    'item_id' => $itemId,
                    'item_name' => $data['item_name'],
                    'item_code' => $data['item_code'],
                    'purchases' => $this->formatPurchasesSummaryCell($data['purchases'], $data['unit'], $currencyId),
                    'usage' => $this->formatUsageSummaryCell($data['usage'], $data['unit']),
                    'movements' => $this->formatMovementsSummaryCell($data['movements'], $data['unit']),
                    'wastages' => $this->formatWastageSummaryCell($data['wastages'], $data['unit']),
                    'has_purchases' => $data['purchases']['lines'] > 0,
                    'has_usage' => $data['usage']['entries'] > 0,
                    'has_movements' => $data['movements']['transfers'] > 0,
                    'has_wastages' => $data['wastages']['entries'] > 0,
                ];
            })
            ->filter(function ($row) use ($filters) {
                $activityType = (string) ($filters['activity_type'] ?? 'all');

                return match ($activityType) {
                    'purchases' => $row->has_purchases,
                    'usage' => $row->has_usage,
                    'movements' => $row->has_movements,
                    'wastages' => $row->has_wastages,
                    default => true,
                };
            })
            ->sortBy(fn ($row) => mb_strtolower($row->item_name))
            ->values();
    }

    /**
     * @param  array{received: float, lines: int, subtotal: float}  $data
     */
    protected function formatPurchasesSummaryCell(array $data, string $unit, mixed $currencyId): string
    {
        if ($data['lines'] === 0) {
            return $this->emptySummaryCell();
        }

        return trans('inventory::modules.reports.item_inventory.summary.purchases', [
            'qty' => $this->formatQuantity($data['received']),
            'unit' => $unit,
            'count' => $data['lines'],
            'subtotal' => currency_format($data['subtotal'], $currencyId),
        ]);
    }

    /**
     * @param  array{qty: float, entries: int}  $data
     */
    protected function formatUsageSummaryCell(array $data, string $unit): string
    {
        if ($data['entries'] === 0) {
            return $this->emptySummaryCell();
        }

        return trans('inventory::modules.reports.item_inventory.summary.usage', [
            'qty' => $this->formatQuantity($data['qty']),
            'unit' => $unit,
            'count' => $data['entries'],
        ]);
    }

    /**
     * @param  array{out: float, in: float, transfers: int}  $data
     */
    protected function formatMovementsSummaryCell(array $data, string $unit): string
    {
        if ($data['transfers'] === 0) {
            return $this->emptySummaryCell();
        }

        return trans('inventory::modules.reports.item_inventory.summary.movements', [
            'out' => $this->formatQuantity($data['out']),
            'in' => $this->formatQuantity($data['in']),
            'unit' => $unit,
            'count' => $data['transfers'],
        ]);
    }

    /**
     * @param  array{qty: float, entries: int}  $data
     */
    protected function formatWastageSummaryCell(array $data, string $unit): string
    {
        if ($data['entries'] === 0) {
            return $this->emptySummaryCell();
        }

        return trans('inventory::modules.reports.item_inventory.summary.wastages', [
            'qty' => $this->formatQuantity($data['qty']),
            'unit' => $unit,
            'count' => $data['entries'],
        ]);
    }

    protected function emptySummaryCell(): string
    {
        return trans('inventory::modules.reports.item_inventory.summary.empty');
    }

    public function formatQuantity(float $qty): string
    {
        $formatted = number_format($qty, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
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

    public function resolveLocationLabel(int $restaurantId, ?int $locationId): string
    {
        if (! $locationId) {
            return trans('app.all');
        }

        $location = PurchaseLocation::query()
            ->where('restaurant_id', $restaurantId)
            ->find($locationId);

        return $location?->name ?? trans('app.all');
    }

    public function resolveItemLabel(int $restaurantId, ?int $itemId): string
    {
        if (! $itemId) {
            return trans('app.all');
        }

        $item = \Modules\Inventory\Entities\InventoryItem::query()
            ->where('restaurant_id', $restaurantId)
            ->find($itemId);

        if (! $item) {
            return trans('app.all');
        }

        return $item->item_code
            ? "{$item->name} ({$item->item_code})"
            : $item->name;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyBranchFilterOnColumn($query, string $column, array $filters): void
    {
        $branch = $filters['branch_filter'] ?? 'all';

        if ($branch !== '' && $branch !== 'all') {
            $query->where($column, (int) $branch);
        }
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

    protected function applyPaymentStatusFilter($query, string $paymentStatus): void
    {
        if ($paymentStatus === '') {
            return;
        }

        $paidSub = '(SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE purchase_order_id = purchase_orders.id)';
        $totalExpr = 'COALESCE(purchase_orders.total_amount, 0)';

        match ($paymentStatus) {
            'paid' => $query->whereRaw("{$paidSub} >= {$totalExpr} AND {$totalExpr} > 0"),
            'partial' => $query->whereRaw("{$paidSub} > 0 AND {$paidSub} < {$totalExpr}"),
            'due' => $query->whereRaw("{$paidSub} = 0"),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyItemFilters($query, array $filters, string $itemIdColumn, string $relationName): void
    {
        if (! empty($filters['item_id'])) {
            $query->where($itemIdColumn, (int) $filters['item_id']);
        }

        if (trim($filters['search'] ?? '') === '') {
            return;
        }

        $term = '%' . trim($filters['search']) . '%';
        $query->whereHas($relationName, function ($itemQuery) use ($term) {
            $itemQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('item_code', 'like', $term);
            });
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyLocationFilter($query, array $filters, string $column): void
    {
        if (empty($filters['location_id'])) {
            return;
        }

        $query->where($column, (int) $filters['location_id']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyTransferLocationFilter($query, array $filters): void
    {
        if (empty($filters['location_id'])) {
            return;
        }

        $locationId = (int) $filters['location_id'];
        $query->where(function ($q) use ($locationId) {
            $q->where('source_location_id', $locationId)
                ->orWhere('destination_location_id', $locationId);
        });
    }
}
