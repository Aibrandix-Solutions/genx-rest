<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_designations')) {
            return;
        }

        Schema::create('hrm_designations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('restaurant_id');
            $table->index('department_id');
            $table->unique(['restaurant_id', 'name'], 'hrm_designations_restaurant_name_unique');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('hrm_departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_designations');
    }
};
