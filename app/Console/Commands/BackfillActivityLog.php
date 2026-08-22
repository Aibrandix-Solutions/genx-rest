<?php

namespace App\Console\Commands;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\KotItemAdjustment;
use App\Support\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CashRegister\Entities\CashRegisterTransaction;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\PurchaseOrderAuditLog;
use Modules\Inventory\Entities\SupplierPayment;
use Modules\Inventory\Support\PurchasePaymentActivityLogger;

class BackfillActivityLog extends Command
{
    protected $signature = 'activity-log:backfill {--restaurant= : Limit to a restaurant ID}';

    protected $description = 'Backfill activity_logs from legacy audit tables (idempotent)';

    public function handle(): int
    {
        if (!Schema::hasTable('activity_logs')) {
            $this->error('activity_logs table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $restaurantFilter = $this->option('restaurant') ? (int) $this->option('restaurant') : null;

        $kotCount = $this->backfillKotAdjustments($restaurantFilter);
        $poCount = $this->backfillPurchaseOrderAuditLogs($restaurantFilter);
        $cashCount = $this->backfillCashRegisterTransactions($restaurantFilter);
        $movementCount = $this->backfillInventoryMovements($restaurantFilter);
        $paymentCount = $this->backfillSupplierPayments($restaurantFilter);

        $this->info("Backfill complete: {$kotCount} KOT, {$poCount} PO, {$cashCount} cash, {$movementCount} inventory movements, {$paymentCount} supplier payments.");

        return self::SUCCESS;
    }

    protected function backfillKotAdjustments(?int $restaurantFilter): int
    {
        if (!Schema::hasTable('kot_item_adjustments')) {
            return 0;
        }

        $count = 0;

        KotItemAdjustment::query()
            ->when($restaurantFilter, fn ($q) => $q->where('restaurant_id', $restaurantFilter))
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    if ($this->legacyExists('kot_item_adjustments', $row->id)) {
                        continue;
                    }

                    $event = match ($row->action) {
                        'deleted' => ActivityEvent::KotItemDeleted,
                        'quantity_updated' => ActivityEvent::KotItemQuantityUpdated,
                        'deleted_from_order' => ActivityEvent::KotItemDeletedFromOrder,
                        default => ActivityEvent::KotItemDeleted,
                    };

                    $description = sprintf(
                        'KOT adjustment: %s on %s (Order %s)',
                        $row->action,
                        $row->menu_item_name ?? 'item',
                        $row->formatted_order_number ?? $row->order_number ?? $row->order_id ?? 'N/A'
                    );

                    ActivityLogger::recordEvent(
                        activityEvent: $event,
                        description: $description,
                        properties: [
                            'order_id' => $row->order_id,
                            'order_number' => $row->order_number,
                            'formatted_order_number' => $row->formatted_order_number,
                            'table_code' => $row->table_code,
                            'menu_item_name' => $row->menu_item_name,
                            'menu_item_variation_name' => $row->menu_item_variation_name,
                            'action' => $row->action,
                            'quantity_before' => $row->quantity_before,
                            'quantity_after' => $row->quantity_after,
                            'note' => $row->note,
                        ],
                        restaurantId: $row->restaurant_id ? (int) $row->restaurant_id : null,
                        branchId: $row->branch_id ? (int) $row->branch_id : null,
                        causerId: $row->performed_by ? (int) $row->performed_by : null,
                        causerName: $row->performed_by_name,
                        legacySource: 'kot_item_adjustments',
                        legacyId: (int) $row->id,
                        createdAt: $row->created_at,
                    );

                    $count++;
                }
            });

        return $count;
    }

    protected function backfillPurchaseOrderAuditLogs(?int $restaurantFilter): int
    {
        if (!Schema::hasTable('purchase_order_audit_logs')) {
            return 0;
        }

        $count = 0;
        $branchRestaurantMap = Branch::query()->pluck('restaurant_id', 'id');

        PurchaseOrderAuditLog::query()
            ->with(['purchaseOrder:id,branch_id,po_number'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$count, $branchRestaurantMap, $restaurantFilter) {
                foreach ($rows as $row) {
                    if ($this->legacyExists('purchase_order_audit_logs', $row->id)) {
                        continue;
                    }

                    $branchId = $row->purchaseOrder?->branch_id;
                    $restaurantId = $branchId ? ($branchRestaurantMap[$branchId] ?? null) : null;

                    if ($restaurantFilter && (int) $restaurantId !== $restaurantFilter) {
                        continue;
                    }

                    $event = $row->action === 'deleted'
                        ? ActivityEvent::PurchaseOrderDeleted
                        : ActivityEvent::PurchaseOrderAmountUpdated;

                    $poNumber = $row->po_number ?? $row->purchaseOrder?->po_number ?? 'N/A';

                    $description = $row->action === 'deleted'
                        ? "Purchase order {$poNumber} deleted"
                        : "Purchase order {$poNumber} amount updated";

                    ActivityLogger::recordEvent(
                        activityEvent: $event,
                        description: $description,
                        properties: [
                            'purchase_order_id' => $row->purchase_order_id,
                            'po_number' => $poNumber,
                            'action' => $row->action,
                            'old_amount' => $row->old_amount,
                            'new_amount' => $row->new_amount,
                            'amount_difference' => $row->amount_difference,
                            'notes' => $row->notes,
                            'metadata' => $row->metadata,
                        ],
                        restaurantId: $restaurantId ? (int) $restaurantId : null,
                        branchId: $branchId ? (int) $branchId : null,
                        causerId: $row->performed_by ? (int) $row->performed_by : null,
                        legacySource: 'purchase_order_audit_logs',
                        legacyId: (int) $row->id,
                        createdAt: $row->created_at,
                    );

                    $count++;
                }
            });

        return $count;
    }

    protected function backfillCashRegisterTransactions(?int $restaurantFilter): int
    {
        if (!Schema::hasTable('cash_register_transactions')) {
            return 0;
        }

        $count = 0;

        CashRegisterTransaction::query()
            ->when($restaurantFilter, fn ($q) => $q->where('restaurant_id', $restaurantFilter))
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    if ($this->legacyExists('cash_register_transactions', $row->id)) {
                        continue;
                    }

                    $event = match ($row->type) {
                        'cash_in' => ActivityEvent::CashIn,
                        'cash_out' => ActivityEvent::CashOut,
                        'safe_drop' => ActivityEvent::CashSafeDrop,
                        default => ActivityEvent::CashIn,
                    };

                    $description = sprintf(
                        'Cash register %s: %s',
                        str_replace('_', ' ', (string) $row->type),
                        number_format((float) $row->amount, 2)
                    );

                    ActivityLogger::recordEvent(
                        activityEvent: $event,
                        description: $description,
                        properties: [
                            'type' => $row->type,
                            'amount' => $row->amount,
                            'reason' => $row->reason,
                            'reference' => $row->reference,
                            'cash_register_session_id' => $row->cash_register_session_id,
                            'running_amount' => $row->running_amount,
                        ],
                        restaurantId: $row->restaurant_id ? (int) $row->restaurant_id : null,
                        branchId: $row->branch_id ? (int) $row->branch_id : null,
                        causerId: $row->created_by ? (int) $row->created_by : null,
                        legacySource: 'cash_register_transactions',
                        legacyId: (int) $row->id,
                        createdAt: $row->happened_at ?? $row->created_at,
                    );

                    $count++;
                }
            });

        return $count;
    }

    protected function backfillInventoryMovements(?int $restaurantFilter): int
    {
        if (!Schema::hasTable('inventory_movements')) {
            return 0;
        }

        $count = 0;
        $branchRestaurantMap = Branch::query()->pluck('restaurant_id', 'id');

        InventoryMovement::query()
            ->with('item:id,name')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$count, $branchRestaurantMap, $restaurantFilter) {
                foreach ($rows as $row) {
                    if ($this->legacyExists('inventory_movements', $row->id)) {
                        continue;
                    }

                    $restaurantId = $branchRestaurantMap[$row->branch_id] ?? null;

                    if ($restaurantFilter && (int) $restaurantId !== $restaurantFilter) {
                        continue;
                    }

                    $itemName = $row->item?->name ?? 'item';

                    $description = sprintf(
                        'Inventory %s: %s x %s',
                        $row->transaction_type,
                        $row->quantity,
                        $itemName
                    );

                    ActivityLogger::recordEvent(
                        activityEvent: ActivityEvent::InventoryMovementCreated,
                        description: $description,
                        properties: [
                            'inventory_item_id' => $row->inventory_item_id,
                            'item_name' => $itemName,
                            'quantity' => $row->quantity,
                            'transaction_type' => $row->transaction_type,
                            'waste_reason' => $row->waste_reason,
                            'unit_purchase_price' => $row->unit_purchase_price,
                            'supplier_id' => $row->supplier_id,
                            'inventory_transfer_id' => $row->inventory_transfer_id,
                        ],
                        restaurantId: $restaurantId ? (int) $restaurantId : null,
                        branchId: $row->branch_id ? (int) $row->branch_id : null,
                        causerId: $row->added_by ? (int) $row->added_by : null,
                        legacySource: 'inventory_movements',
                        legacyId: (int) $row->id,
                        createdAt: $row->created_at,
                    );

                    $count++;
                }
            });

        return $count;
    }

    protected function backfillSupplierPayments(?int $restaurantFilter): int
    {
        if (!Schema::hasTable('supplier_payments')) {
            return 0;
        }

        $count = 0;
        $branchRestaurantMap = Branch::query()->pluck('restaurant_id', 'id');

        SupplierPayment::query()
            ->with(['purchaseOrder:id,branch_id,po_number', 'supplier:id,restaurant_id,name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$count, $branchRestaurantMap, $restaurantFilter) {
                foreach ($rows as $payment) {
                    if ($this->legacyExists('supplier_payments', $payment->id)) {
                        continue;
                    }

                    $restaurantId = $payment->supplier?->restaurant_id
                        ?? ($payment->purchaseOrder?->branch_id
                            ? ($branchRestaurantMap[$payment->purchaseOrder->branch_id] ?? null)
                            : null);

                    if ($restaurantFilter && (int) $restaurantId !== $restaurantFilter) {
                        continue;
                    }

                    PurchasePaymentActivityLogger::logCreatedFromBackfill($payment);
                    $count++;
                }
            });

        return $count;
    }

    protected function legacyExists(string $source, int $legacyId): bool
    {
        return ActivityLog::query()
            ->where('legacy_source', $source)
            ->where('legacy_id', $legacyId)
            ->exists();
    }
}
