<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('unit_price')->comment('Item-level discount');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed')->after('discount')->comment('Discount type for this item');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'discount_type']);
        });
    }
};
