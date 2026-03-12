<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            
            $table->string('name'); // e.g., "Warehouse", "Main Branch", "Warehouse 2"
            $table->enum('type', ['warehouse', 'branch'])->default('warehouse');
            $table->unsignedBigInteger('branch_id')->nullable(); // If type='branch', reference to branch
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_locations');
    }
};
