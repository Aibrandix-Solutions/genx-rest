<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_leave_types')) {
            return;
        }

        Schema::create('hrm_leave_types', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');

            $table->string('name');
            $table->unsignedInteger('max_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'is_active'], 'hrm_leave_types_restaurant_active_idx');
            $table->unique(['restaurant_id', 'name'], 'hrm_leave_types_restaurant_name_unique');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_leave_types');
    }
};
