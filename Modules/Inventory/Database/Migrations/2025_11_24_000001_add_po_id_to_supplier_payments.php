<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('supplier_id');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};

