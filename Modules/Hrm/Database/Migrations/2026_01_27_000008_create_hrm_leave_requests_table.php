<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_leave_requests')) {
            return;
        }

        Schema::create('hrm_leave_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');

            $table->date('from_date');
            $table->date('to_date');

            $table->string('status')->default('approved'); // pending/approved/rejected/cancelled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->string('reason')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id'], 'hrm_leave_requests_restaurant_branch_idx');
            $table->index(['employee_id', 'from_date', 'to_date'], 'hrm_leave_requests_employee_range_idx');
            $table->index(['status', 'from_date', 'to_date'], 'hrm_leave_requests_status_range_idx');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hrm_employees')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('hrm_leave_types')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_leave_requests');
    }
};
