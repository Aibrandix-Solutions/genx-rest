<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_settings')) {
            return;
        }

        Schema::table('hotel_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_settings', 'enable_payment_surcharge')) {
                $table->boolean('enable_payment_surcharge')->default(false)->after('service_charge_rate');
            }
            if (!Schema::hasColumn('hotel_settings', 'payment_surcharge_rate')) {
                $table->decimal('payment_surcharge_rate', 5, 2)->default(0)->after('enable_payment_surcharge');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hotel_settings')) {
            return;
        }

        Schema::table('hotel_settings', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_settings', 'payment_surcharge_rate')) {
                $table->dropColumn('payment_surcharge_rate');
            }
            if (Schema::hasColumn('hotel_settings', 'enable_payment_surcharge')) {
                $table->dropColumn('enable_payment_surcharge');
            }
        });
    }
};
