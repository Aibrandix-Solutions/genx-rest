<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'kot',
                'billed',
                'paid',
                'canceled',
                'payment_due',
                'ready',
                'out_for_delivery',
                'delivered',
                'pending_verification',
                'folio_settled',
            ])->default('kot')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'kot',
                'billed',
                'paid',
                'canceled',
                'payment_due',
                'ready',
                'out_for_delivery',
                'delivered',
                'pending_verification',
            ])->default('kot')->change();
        });
    }
};
