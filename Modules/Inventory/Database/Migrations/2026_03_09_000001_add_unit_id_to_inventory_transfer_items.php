<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('destination_inventory_item_id');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};
