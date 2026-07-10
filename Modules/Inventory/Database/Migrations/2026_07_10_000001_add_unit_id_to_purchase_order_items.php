<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('inventory_item_id');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });

        DB::table('purchase_order_items')
            ->join('inventory_items', 'purchase_order_items.inventory_item_id', '=', 'inventory_items.id')
            ->whereNull('purchase_order_items.unit_id')
            ->update(['purchase_order_items.unit_id' => DB::raw('inventory_items.unit_id')]);
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};
