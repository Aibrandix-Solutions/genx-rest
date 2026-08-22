<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_attendance_logs')) {
            return;
        }

        Schema::create('hrm_attendance_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('shift_id')->nullable();

            $table->date('date');
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();

            $table->string('status')->default('present'); // present/absent/late/half_day/leave
            $table->unsignedInteger('late_minutes')->default(0);
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id', 'date'], 'hrm_attendance_restaurant_branch_date_idx');
            $table->unique(['restaurant_id', 'employee_id', 'date'], 'hrm_attendance_restaurant_employee_date_unique');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hrm_employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('hrm_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_attendance_logs');
    }
};
