<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_return_id')->nullable()->after('purchase_order_id');
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropColumn('purchase_return_id');
        });
    }
};


