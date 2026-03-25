<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a composite unique index on (branch_id, item_code) so the database
     * enforces branch-scoped uniqueness, preventing race-condition duplicates
     * that application-level validation alone cannot catch.
     *
     * NULL item_code values are intentionally excluded from the index
     * (WHERE item_code IS NOT NULL) so items without a code never conflict.
     *
     * Before adding the index we deduplicate any existing rows that would
     * violate it — keeping the row with the smallest id in each conflict group.
     */
    public function up(): void
    {
        // Resolve any existing duplicates before creating the constraint.
        // Keeps the earliest row (lowest id) per (branch_id, item_code) pair.
        DB::statement("
            DELETE mi
            FROM menu_items mi
            INNER JOIN (
                SELECT MIN(id) AS keep_id, branch_id, item_code
                FROM menu_items
                WHERE item_code IS NOT NULL
                  AND item_code != ''
                GROUP BY branch_id, item_code
                HAVING COUNT(*) > 1
            ) dupes
                ON  mi.branch_id  = dupes.branch_id
                AND mi.item_code  = dupes.item_code
                AND mi.id        != dupes.keep_id
        ");

        Schema::table('menu_items', function (Blueprint $table) {
            // Composite unique: one code per branch (NULLs are excluded by MySQL
            // because the partial-index equivalent is handled by nullable itself —
            // MySQL unique indexes do not consider two NULLs as duplicates).
            $table->unique(['branch_id', 'item_code'], 'menu_items_branch_item_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropUnique('menu_items_branch_item_code_unique');
        });
    }
};
