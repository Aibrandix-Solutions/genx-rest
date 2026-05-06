<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_shifts')) {
            return;
        }

        Schema::create('hrm_shifts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('grace_minutes')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id'], 'hrm_shifts_restaurant_branch_idx');
            $table->unique(['restaurant_id', 'branch_id', 'name'], 'hrm_shifts_restaurant_branch_name_unique');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_shifts');
    }
};
