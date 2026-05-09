<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->boolean('is_epf_eligible')->default(true)->after('basic_salary_per_month')
                ->comment('Whether this employee is eligible for EPF deduction');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_employees', function (Blueprint $table) {
            $table->dropColumn('is_epf_eligible');
        });
    }
};
