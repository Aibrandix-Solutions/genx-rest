<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade')->onUpdate('cascade');
            
            $table->string('transfer_number')->unique();
            
            $table->unsignedBigInteger('source_branch_id');
            $table->foreign('source_branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('destination_branch_id');
            $table->foreign('destination_branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            
            $table->enum('status', ['pending', 'in_transit', 'completed', 'cancelled'])->default('pending');
            
            $table->text('notes')->nullable();
            $table->date('expected_delivery_date')->nullable();
            
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamp('confirmed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['source_branch_id', 'status']);
            $table->index(['destination_branch_id', 'status']);
            $table->index('restaurant_id');
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('inventory_transfer_id');
            $table->foreign('inventory_transfer_id')->references('id')->on('inventory_transfers')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('source_inventory_item_id');
            $table->foreign('source_inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('destination_inventory_item_id');
            $table->foreign('destination_inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade')->onUpdate('cascade');
            
            $table->decimal('requested_quantity', 16, 2);
            $table->decimal('confirmed_quantity', 16, 2)->nullable();
            
            $table->enum('status', ['pending', 'in_transit', 'completed', 'partially_received', 'cancelled'])->default('pending');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('inventory_transfer_id');
            $table->index(['source_inventory_item_id', 'destination_inventory_item_id'], 'transfer_items_source_dest_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
    }
};

