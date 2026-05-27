<?php

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\SupplierPayment;

/**
 * One-shot cleanup command.
 *
 * Existing supplier_payments rows that were created via the old "Pay Supplier"
 * flow have purchase_order_id = NULL. They reduce the supplier balance but do
 * not reduce any individual PO's due_amount, which causes the Supplier view
 * and the Purchases page to show different numbers.
 *
 * This command walks every supplier and reallocates each orphan payment
 * across that supplier's still-due received purchase orders in FIFO order
 * (oldest order_date first). One orphan row may be split into multiple
 * rows, one per PO it gets allocated to. Any leftover (true overpayment /
 * advance) stays as an orphan row so the supplier balance stays the same.
 */
class ReallocateOrphanSupplierPayments extends Command
{
    protected $signature = 'inventory:reallocate-orphan-supplier-payments
                            {--supplier= : Limit to a single supplier id}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Attach legacy supplier_payments rows (purchase_order_id NULL) to due POs in FIFO order so Supplier view and Purchases page agree.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $supplierId = $this->option('supplier');

        $suppliersQuery = Supplier::query();
        if ($supplierId) {
            $suppliersQuery->whereKey($supplierId);
        }

        $suppliers = $suppliersQuery->get();
        if ($suppliers->isEmpty()) {
            $this->warn('No suppliers found.');
            return self::SUCCESS;
        }

        $totalAllocated = 0.0;
        $totalRows = 0;

        foreach ($suppliers as $supplier) {
            // Orphan payments only — never touch payments already tied to a PO
            $orphans = SupplierPayment::where('supplier_id', $supplier->id)
                ->whereNull('purchase_order_id')
                ->orderBy('paid_on', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($orphans->isEmpty()) {
                continue;
            }

            // FIFO list of due POs for this supplier
            $duePOs = $supplier->orders()
                ->where('status', 'received')
                ->with('payments')
                ->orderBy('order_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Track remaining due per PO in memory so we can allocate across multiple orphans
            $remainingDueByPo = [];
            foreach ($duePOs as $po) {
                $due = (float) $po->due_amount;
                if ($due > 0) {
                    $remainingDueByPo[$po->id] = $due;
                }
            }

            if (empty($remainingDueByPo)) {
                $this->line("Supplier #{$supplier->id} ({$supplier->name}): " . $orphans->count() . ' orphan payment(s) found, but no due purchases. Skipped.');
                continue;
            }

            $this->info("Supplier #{$supplier->id} ({$supplier->name}): processing " . $orphans->count() . ' orphan payment(s).');

            foreach ($orphans as $orphan) {
                $remaining = (float) $orphan->amount;

                // Walk POs in FIFO order
                foreach ($remainingDueByPo as $poId => $dueLeft) {
                    if ($remaining <= 0) {
                        break;
                    }
                    if ($dueLeft <= 0) {
                        continue;
                    }

                    $allocate = round(min($remaining, $dueLeft), 2);

                    if ($dryRun) {
                        $this->line("  - Would allocate {$allocate} from payment #{$orphan->id} -> PO #{$poId}");
                    } else {
                        DB::transaction(function () use ($orphan, $poId, $allocate, &$remaining) {
                            if (round($allocate, 2) === round((float) $orphan->amount, 2) && is_null($orphan->purchase_order_id)) {
                                // Whole payment fits one PO: just attach the existing row
                                $orphan->purchase_order_id = $poId;
                                $orphan->save();
                            } else {
                                // Split: create a new row for this slice, decrement the orphan
                                $clone = $orphan->replicate();
                                $clone->purchase_order_id = $poId;
                                $clone->amount = $allocate;
                                $clone->save();

                                $orphan->amount = round(((float) $orphan->amount) - $allocate, 2);
                                if ($orphan->amount <= 0) {
                                    $orphan->delete();
                                } else {
                                    $orphan->save();
                                }
                            }
                        });
                    }

                    $remainingDueByPo[$poId] = round($dueLeft - $allocate, 2);
                    $remaining = round($remaining - $allocate, 2);
                    $totalAllocated += $allocate;
                    $totalRows++;
                }

                // If $remaining > 0 here it stays as an orphan (true advance / overpayment)
                if ($remaining > 0) {
                    $this->line("  - Payment #{$orphan->id}: {$remaining} left as advance (no due PO).");
                }
            }
        }

        if ($dryRun) {
            $this->warn("DRY RUN: {$totalRows} allocation(s) would be created/updated, total " . number_format($totalAllocated, 2));
        } else {
            $this->info("Done. {$totalRows} allocation(s) written, total " . number_format($totalAllocated, 2));
        }

        return self::SUCCESS;
    }
}
