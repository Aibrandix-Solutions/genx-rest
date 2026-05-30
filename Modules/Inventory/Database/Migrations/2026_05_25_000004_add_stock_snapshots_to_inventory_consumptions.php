<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_consumptions', 'stock_before')) {
                $table->decimal('stock_before', 16, 2)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('inventory_consumptions', 'stock_after')) {
                $table->decimal('stock_after', 16, 2)->nullable()->after('stock_before');
            }
        });

        // Best-effort backfill for existing rows so reports look sensible
        // immediately after upgrade. We approximate `stock_after` per item using
        // the current on-hand quantity plus the sum of all later consumptions
        // for that item, then derive `stock_before = stock_after + quantity`.
        try {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE inventory_consumptions ic
                JOIN (
                    SELECT
                        c1.id,
                        c1.inventory_item_id,
                        c1.quantity,
                        (
                            COALESCE((SELECT SUM(s.quantity) FROM inventory_stocks s WHERE s.inventory_item_id = c1.inventory_item_id), 0)
                            +
                            COALESCE((
                                SELECT SUM(c2.quantity) FROM inventory_consumptions c2
                                WHERE c2.inventory_item_id = c1.inventory_item_id
                                  AND (c2.created_at > c1.created_at OR (c2.created_at = c1.created_at AND c2.id > c1.id))
                            ), 0)
                        ) AS computed_after
                    FROM inventory_consumptions c1
                    WHERE c1.stock_after IS NULL OR c1.stock_before IS NULL
                ) calc ON calc.id = ic.id
                SET ic.stock_after = calc.computed_after,
                    ic.stock_before = calc.computed_after + calc.quantity
            ");
        } catch (\Throwable $e) {
            // Backfill is best-effort; leave nulls if anything goes wrong.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_consumptions', 'stock_after')) {
                $table->dropColumn('stock_after');
            }
            if (Schema::hasColumn('inventory_consumptions', 'stock_before')) {
                $table->dropColumn('stock_before');
            }
        });
    }
};
