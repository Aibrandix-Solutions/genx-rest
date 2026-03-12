<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cash_register_transactions')) {
            return;
        }

        if (!Schema::hasColumn('cash_register_transactions', 'order_id') || !Schema::hasColumn('cash_register_transactions', 'type')) {
            return;
        }

        // Clean duplicates before adding uniqueness (keep latest id per (order_id,type))
        // Only applies to rows with non-null order_id.
        DB::statement(
            "DELETE t1 FROM cash_register_transactions t1\n"
            . "INNER JOIN cash_register_transactions t2\n"
            . "  ON t1.order_id = t2.order_id\n"
            . " AND t1.type = t2.type\n"
            . " AND t1.id < t2.id\n"
            . "WHERE t1.order_id IS NOT NULL"
        );

        $indexName = 'cash_register_transactions_order_id_type_unique';

        $existing = DB::select("SHOW INDEX FROM cash_register_transactions WHERE Key_name = ?", [$indexName]);
        if (!empty($existing)) {
            return;
        }

        Schema::table('cash_register_transactions', function (Blueprint $table) use ($indexName) {
            $table->unique(['order_id', 'type'], $indexName);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cash_register_transactions')) {
            return;
        }

        $indexName = 'cash_register_transactions_order_id_type_unique';

        $existing = DB::select("SHOW INDEX FROM cash_register_transactions WHERE Key_name = ?", [$indexName]);
        if (empty($existing)) {
            return;
        }

        Schema::table('cash_register_transactions', function (Blueprint $table) use ($indexName) {
            $table->dropUnique($indexName);
        });
    }
};
