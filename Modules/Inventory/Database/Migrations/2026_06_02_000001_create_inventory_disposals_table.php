<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_disposals')) {
            Schema::create('inventory_disposals', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('restaurant_id');
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade')->onUpdate('cascade');

                $table->unsignedBigInteger('branch_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');

                $table->unsignedBigInteger('inventory_item_id');
                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade')->onUpdate('cascade');

                $table->unsignedBigInteger('location_id')->nullable();
                $table->foreign('location_id')->references('id')->on('purchase_locations')->onDelete('set null')->onUpdate('cascade');

                $table->decimal('quantity', 16, 2)->default(0);
                $table->decimal('stock_before', 16, 2)->nullable();
                $table->decimal('stock_after', 16, 2)->nullable();
                $table->string('reason', 255)->nullable();
                $table->date('disposal_date');

                $table->unsignedBigInteger('added_by')->nullable();
                $table->foreign('added_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');

                $table->timestamps();

                $table->index(['restaurant_id', 'branch_id', 'disposal_date'], 'id_rest_branch_date_idx');
                $table->index(['inventory_item_id', 'disposal_date'], 'id_item_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_disposals');
    }
};
