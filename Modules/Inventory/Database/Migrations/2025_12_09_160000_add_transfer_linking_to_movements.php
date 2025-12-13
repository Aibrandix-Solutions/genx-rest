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
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_transfer_id')->nullable()->after('transfer_branch_id');
            $table->unsignedBigInteger('inventory_transfer_item_id')->nullable()->after('inventory_transfer_id');
            
            $table->foreign('inventory_transfer_id')
                ->references('id')
                ->on('inventory_transfers')
                ->onDelete('set null')
                ->onUpdate('cascade');
                
            $table->foreign('inventory_transfer_item_id')
                ->references('id')
                ->on('inventory_transfer_items')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['inventory_transfer_id']);
            $table->dropForeign(['inventory_transfer_item_id']);
            $table->dropColumn(['inventory_transfer_id', 'inventory_transfer_item_id']);
        });
    }
};

