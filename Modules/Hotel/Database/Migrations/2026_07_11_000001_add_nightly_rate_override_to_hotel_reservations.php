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

        if (Schema::hasColumn('hotel_reservations', 'nightly_rate_override')) {
            return;
        }

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->decimal('nightly_rate_override', 10, 2)->nullable()->after('tax_rate_override');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hotel_reservations')) {
            return;
        }

        if (!Schema::hasColumn('hotel_reservations', 'nightly_rate_override')) {
            return;
        }

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropColumn('nightly_rate_override');
        });
    }
};
