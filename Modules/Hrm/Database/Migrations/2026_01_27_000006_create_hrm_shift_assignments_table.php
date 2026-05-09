<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_shift_assignments')) {
            return;
        }

        Schema::create('hrm_shift_assignments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('shift_id');

            $table->date('from_date');
            $table->date('to_date');

            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id'], 'hrm_shift_assign_restaurant_branch_idx');
            $table->index(['employee_id', 'from_date', 'to_date'], 'hrm_shift_assign_employee_range_idx');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hrm_employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('hrm_shifts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_shift_assignments');
    }
};
