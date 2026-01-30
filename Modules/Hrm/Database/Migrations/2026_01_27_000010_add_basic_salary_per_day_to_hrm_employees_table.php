<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hrm_employees')) {
            return;
        }

        Schema::table('hrm_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('hrm_employees', 'basic_salary_per_day')) {
                $table->decimal('basic_salary_per_day', 12, 2)->default(0)->after('employment_type');
            }
            if (!Schema::hasColumn('hrm_employees', 'basic_salary_per_month')) {
                $table->decimal('basic_salary_per_month', 12, 2)->default(0)->after('basic_salary_per_day');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hrm_employees')) {
            return;
        }

        Schema::table('hrm_employees', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_employees', 'basic_salary_per_month')) {
                $table->dropColumn('basic_salary_per_month');
            }
            if (Schema::hasColumn('hrm_employees', 'basic_salary_per_day')) {
                $table->dropColumn('basic_salary_per_day');
            }
        });
    }
};
