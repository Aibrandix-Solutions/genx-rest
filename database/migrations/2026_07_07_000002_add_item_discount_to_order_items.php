<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'discount_type')) {
                $table->string('discount_type', 20)->nullable()->after('combo_discount_amount');
            }
            if (! Schema::hasColumn('order_items', 'discount_value')) {
                $table->decimal('discount_value', 16, 2)->nullable()->after('discount_type');
            }
            if (! Schema::hasColumn('order_items', 'item_discount_amount')) {
                $table->decimal('item_discount_amount', 16, 2)->nullable()->after('discount_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $columns = ['item_discount_amount', 'discount_value', 'discount_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
