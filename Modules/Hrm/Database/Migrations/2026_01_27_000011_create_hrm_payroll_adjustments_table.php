<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_payroll_adjustments')) {
            return;
        }

        Schema::create('hrm_payroll_adjustments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->decimal('additional_pay', 12, 2)->default(0);
            $table->decimal('advance', 12, 2)->default(0);
            $table->decimal('epf', 12, 2)->default(0);
            $table->decimal('etf', 12, 2)->default(0);
            $table->decimal('time_deduction', 12, 2)->default(0);
            $table->decimal('credit_purchase', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);

            $table->date('payment_date')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['restaurant_id', 'branch_id', 'employee_id', 'year', 'month'], 'hrm_payroll_adjust_unique');
            $table->index(['restaurant_id', 'branch_id', 'year', 'month'], 'hrm_payroll_adjust_restaurant_branch_month_idx');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hrm_employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_payroll_adjustments');
    }
};
