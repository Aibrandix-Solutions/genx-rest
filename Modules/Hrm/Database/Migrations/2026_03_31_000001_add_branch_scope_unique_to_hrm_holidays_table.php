<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hrm_holidays')) {
            return;
        }

        if (!Schema::hasColumn('hrm_holidays', 'branch_scope')) {
            Schema::table('hrm_holidays', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_scope')
                    ->storedAs('IFNULL(branch_id, 0)')
                    ->after('branch_id');
            });
        }

        // Replace nullable-branch unique key with deterministic branch_scope uniqueness.
        Schema::table('hrm_holidays', function (Blueprint $table) {
            try {
                $table->dropUnique('hrm_holidays_unique');
            } catch (\Throwable $e) {
                // ignore if missing
            }
            try {
                $table->unique(['restaurant_id', 'date', 'name', 'branch_scope'], 'hrm_holidays_unique_scope');
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hrm_holidays')) {
            return;
        }

        Schema::table('hrm_holidays', function (Blueprint $table) {
            try {
                $table->dropUnique('hrm_holidays_unique_scope');
            } catch (\Throwable $e) {
                // ignore if missing
            }
            try {
                $table->unique(['restaurant_id', 'branch_id', 'date', 'name'], 'hrm_holidays_unique');
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        });

        if (Schema::hasColumn('hrm_holidays', 'branch_scope')) {
            Schema::table('hrm_holidays', function (Blueprint $table) {
                $table->dropColumn('branch_scope');
            });
        }
    }
};
