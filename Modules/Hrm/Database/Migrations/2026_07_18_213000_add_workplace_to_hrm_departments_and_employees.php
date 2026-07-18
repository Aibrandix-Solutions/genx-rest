<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_departments', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_departments', 'workplace')) {
                $table->string('workplace', 20)->default('restaurant')->after('restaurant_id');
            }
        });

        Schema::table('hrm_departments', function (Blueprint $table) {
            $sm = Schema::getConnection()->getSchemaBuilder();
            // Drop legacy unique (restaurant_id, name) if it exists
        });

        try {
            Schema::table('hrm_departments', function (Blueprint $table) {
                $table->dropUnique('hrm_departments_restaurant_id_name_unique');
            });
        } catch (\Throwable) {
            try {
                DB::statement('ALTER TABLE hrm_departments DROP INDEX hrm_departments_restaurant_id_name_unique');
            } catch (\Throwable) {
            }
        }

        Schema::table('hrm_departments', function (Blueprint $table) {
            $table->index(['restaurant_id', 'workplace'], 'hrm_departments_restaurant_workplace_idx');
            $table->unique(['restaurant_id', 'workplace', 'name'], 'hrm_departments_restaurant_workplace_name_unique');
        });

        Schema::table('hrm_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_employees', 'workplace')) {
                $table->string('workplace', 20)->default('restaurant')->after('branch_id');
                $table->index(['restaurant_id', 'workplace'], 'hrm_employees_restaurant_workplace_idx');
            }
        });

        Schema::table('hrm_payroll_adjustments', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_payroll_adjustments', 'restaurant_expense_id')) {
                $table->unsignedBigInteger('restaurant_expense_id')->nullable()->after('note');
            }
            if (! Schema::hasColumn('hrm_payroll_adjustments', 'hotel_expense_id')) {
                $table->unsignedBigInteger('hotel_expense_id')->nullable()->after('restaurant_expense_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hrm_payroll_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_payroll_adjustments', 'restaurant_expense_id')) {
                $table->dropColumn('restaurant_expense_id');
            }
            if (Schema::hasColumn('hrm_payroll_adjustments', 'hotel_expense_id')) {
                $table->dropColumn('hotel_expense_id');
            }
        });

        Schema::table('hrm_employees', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_employees', 'workplace')) {
                try {
                    $table->dropIndex('hrm_employees_restaurant_workplace_idx');
                } catch (\Throwable) {
                }
                $table->dropColumn('workplace');
            }
        });

        Schema::table('hrm_departments', function (Blueprint $table) {
            try {
                $table->dropUnique('hrm_departments_restaurant_workplace_name_unique');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('hrm_departments_restaurant_workplace_idx');
            } catch (\Throwable) {
            }
            if (Schema::hasColumn('hrm_departments', 'workplace')) {
                $table->dropColumn('workplace');
            }
            $table->unique(['restaurant_id', 'name'], 'hrm_departments_restaurant_id_name_unique');
        });
    }
};
