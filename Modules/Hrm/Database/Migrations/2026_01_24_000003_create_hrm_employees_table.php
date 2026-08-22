<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_employees')) {
            return;
        }

        Schema::create('hrm_employees', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();

            $table->string('staff_code')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->date('hire_date')->nullable();
            $table->string('employment_type')->default('full_time');

            $table->string('status')->default('active'); // active/inactive/terminated
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id'], 'hrm_employees_restaurant_branch_idx');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('designation_id');

            $table->unique(['restaurant_id', 'staff_code'], 'hrm_employees_restaurant_staff_code_unique');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('hrm_departments')->nullOnDelete();
            $table->foreign('designation_id')->references('id')->on('hrm_designations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employees');
    }
};
