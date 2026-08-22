<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_payments', 'payment_batch_id')) {
                $table->string('payment_batch_id', 36)->nullable()->after('supplier_id');
                $table->index('payment_batch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'payment_batch_id')) {
                $table->dropIndex(['payment_batch_id']);
                $table->dropColumn('payment_batch_id');
            }
        });
    }
};
