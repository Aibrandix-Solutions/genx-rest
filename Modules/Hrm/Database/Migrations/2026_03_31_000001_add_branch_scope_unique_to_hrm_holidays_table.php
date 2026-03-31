<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);
        return !empty($result);
    }

    public function up(): void
    {
        if (!Schema::hasTable('hrm_holidays')) {
            return;
        }

        if (!Schema::hasColumn('hrm_holidays', 'branch_scope')) {
            Schema::table('hrm_holidays', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_scope')
                    ->default(0)
                    ->after('branch_id');
            });
        }

        // Backfill deterministic scope value for existing rows.
        DB::table('hrm_holidays')->update([
            'branch_scope' => DB::raw('COALESCE(branch_id, 0)'),
        ]);

        // Remove duplicates that become invalid under deterministic branch scope.
        DB::statement("
            DELETE h1
            FROM hrm_holidays h1
            INNER JOIN hrm_holidays h2
                ON h1.id > h2.id
               AND h1.restaurant_id = h2.restaurant_id
               AND h1.date = h2.date
               AND h1.name = h2.name
               AND COALESCE(h1.branch_id, 0) = COALESCE(h2.branch_id, 0)
        ");

        // Replace nullable-branch unique key with deterministic branch_scope uniqueness.
        if ($this->indexExists('hrm_holidays', 'hrm_holidays_unique')) {
            DB::statement('DROP INDEX `hrm_holidays_unique` ON `hrm_holidays`');
        }

        if (!$this->indexExists('hrm_holidays', 'hrm_holidays_unique_scope')) {
            Schema::table('hrm_holidays', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'date', 'name', 'branch_scope'], 'hrm_holidays_unique_scope');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('hrm_holidays')) {
            return;
        }

        if ($this->indexExists('hrm_holidays', 'hrm_holidays_unique_scope')) {
            DB::statement('DROP INDEX `hrm_holidays_unique_scope` ON `hrm_holidays`');
        }

        if (!$this->indexExists('hrm_holidays', 'hrm_holidays_unique')) {
            Schema::table('hrm_holidays', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'branch_id', 'date', 'name'], 'hrm_holidays_unique');
            });
        }

        if (Schema::hasColumn('hrm_holidays', 'branch_scope')) {
            Schema::table('hrm_holidays', function (Blueprint $table) {
                $table->dropColumn('branch_scope');
            });
        }
    }
};
