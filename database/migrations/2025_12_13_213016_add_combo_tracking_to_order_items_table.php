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
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('combo_pack_id')->nullable()->after('menu_item_variation_id');
            $table->decimal('original_price', 16, 2)->nullable()->after('price');
            $table->decimal('combo_discount_amount', 16, 2)->nullable()->after('original_price');
            $table->boolean('is_combo_item')->default(false)->after('combo_discount_amount');

            $table->foreign('combo_pack_id')->references('id')->on('combo_packs')->onDelete('set null');
            $table->index('combo_pack_id');
            $table->index('is_combo_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['combo_pack_id']);
            $table->dropIndex(['combo_pack_id']);
            $table->dropIndex(['is_combo_item']);
            $table->dropColumn(['combo_pack_id', 'original_price', 'combo_discount_amount', 'is_combo_item']);
        });
    }
};
