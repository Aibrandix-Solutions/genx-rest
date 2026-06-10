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
            if (! Schema::hasColumn('orders', 'charged_to_folio_at')) {
                $table->timestamp('charged_to_folio_at')->nullable()->after('hotel_reservation_id');
            }
            if (! Schema::hasColumn('orders', 'folio_settled_at')) {
                $table->timestamp('folio_settled_at')->nullable()->after('charged_to_folio_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'folio_settled_at')) {
                $table->dropColumn('folio_settled_at');
            }
            if (Schema::hasColumn('orders', 'charged_to_folio_at')) {
                $table->dropColumn('charged_to_folio_at');
            }
        });
    }
};
