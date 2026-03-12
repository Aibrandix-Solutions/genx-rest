<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addQuantityAndDedupe('cart_item_modifier_options', 'cart_item_id', 'cimo_cart_mod_opt_unique');
        $this->addQuantityAndDedupe('order_item_modifier_options', 'order_item_id', 'oimo_order_mod_opt_unique');
        $this->addQuantityAndDedupe('kot_item_modifier_options', 'kot_item_id', 'kimo_kot_mod_opt_unique');
    }

    public function down(): void
    {
        $this->dropUniqueAndQuantity('cart_item_modifier_options', 'cimo_cart_mod_opt_unique');
        $this->dropUniqueAndQuantity('order_item_modifier_options', 'oimo_order_mod_opt_unique');
        $this->dropUniqueAndQuantity('kot_item_modifier_options', 'kimo_kot_mod_opt_unique');
    }

    private function addQuantityAndDedupe(string $table, string $ownerIdColumn, string $uniqueIndexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, 'quantity')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('quantity')->default(1)->after('modifier_option_id');
            });
        }

        // Consolidate any duplicate rows into a single row with quantity.
        $duplicates = DB::table($table)
            ->select(
                $ownerIdColumn,
                'modifier_option_id',
                DB::raw('COUNT(*) as row_count'),
                DB::raw('MIN(id) as keep_id')
            )
            ->whereNotNull($ownerIdColumn)
            ->whereNotNull('modifier_option_id')
            ->groupBy($ownerIdColumn, 'modifier_option_id')
            ->having('row_count', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $ownerId = $dup->{$ownerIdColumn};

            DB::table($table)
                ->where('id', $dup->keep_id)
                ->update(['quantity' => (int) $dup->row_count]);

            DB::table($table)
                ->where($ownerIdColumn, $ownerId)
                ->where('modifier_option_id', $dup->modifier_option_id)
                ->where('id', '<>', $dup->keep_id)
                ->delete();
        }

        // Ensure one row per (owner, option).
        try {
            Schema::table($table, function (Blueprint $table) use ($ownerIdColumn, $uniqueIndexName) {
                $table->unique([$ownerIdColumn, 'modifier_option_id'], $uniqueIndexName);
            });
        } catch (Throwable $e) {
            // Ignore if it already exists or can't be created.
        }
    }

    private function dropUniqueAndQuantity(string $table, string $uniqueIndexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($uniqueIndexName) {
                $table->dropUnique($uniqueIndexName);
            });
        } catch (Throwable $e) {
            // Ignore if it doesn't exist.
        }

        if (Schema::hasColumn($table, 'quantity')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
