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
        Schema::create('combo_pack_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combo_pack_id');
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedBigInteger('menu_item_variation_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('combo_pack_id')->references('id')->on('combo_packs')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');
            $table->foreign('menu_item_variation_id')->references('id')->on('menu_item_variations')->onDelete('cascade');
            
            $table->index(['combo_pack_id', 'menu_item_id', 'menu_item_variation_id'], 'combo_items_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_pack_items');
    }
};
