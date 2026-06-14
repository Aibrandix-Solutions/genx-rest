<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_reservations')) {
            return;
        }

        if (Schema::hasColumn('hotel_reservations', 'tax_rate_override')) {
            return;
        }

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->decimal('tax_rate_override', 5, 2)->nullable()->after('balance_due');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hotel_reservations')) {
            return;
        }

        if (!Schema::hasColumn('hotel_reservations', 'tax_rate_override')) {
            return;
        }

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropColumn('tax_rate_override');
        });
    }
};
