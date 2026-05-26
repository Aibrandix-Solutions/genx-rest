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
        if (!Schema::hasTable('inventory_consumptions')) {
            Schema::create('inventory_consumptions', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('restaurant_id');
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade')->onUpdate('cascade');

                $table->unsignedBigInteger('branch_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');

                $table->unsignedBigInteger('inventory_item_id');
                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade')->onUpdate('cascade');

                // Optional location tied to the branch (for stock deduction tracking)
                $table->unsignedBigInteger('location_id')->nullable();
                $table->foreign('location_id')->references('id')->on('purchase_locations')->onDelete('set null')->onUpdate('cascade');

                $table->decimal('quantity', 16, 2)->default(0);
                $table->date('consumption_date');
                $table->string('note', 255)->nullable();

                $table->unsignedBigInteger('added_by')->nullable();
                $table->foreign('added_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');

                $table->timestamps();

                $table->index(['restaurant_id', 'branch_id', 'consumption_date'], 'ic_rest_branch_date_idx');
                $table->index(['inventory_item_id', 'consumption_date'], 'ic_item_date_idx');
            });
        }

        if (!Schema::hasTable('inventory_consumption_menu_item')) {
            Schema::create('inventory_consumption_menu_item', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('inventory_consumption_id');
                $table->foreign('inventory_consumption_id', 'icmi_consumption_id_fk')
                    ->references('id')->on('inventory_consumptions')
                    ->onDelete('cascade')->onUpdate('cascade');

                $table->unsignedBigInteger('menu_item_id');
                $table->foreign('menu_item_id', 'icmi_menu_item_id_fk')
                    ->references('id')->on('menu_items')
                    ->onDelete('cascade')->onUpdate('cascade');

                $table->timestamps();

                $table->unique(['inventory_consumption_id', 'menu_item_id'], 'icmi_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_consumption_menu_item');
        Schema::dropIfExists('inventory_consumptions');
    }
};
