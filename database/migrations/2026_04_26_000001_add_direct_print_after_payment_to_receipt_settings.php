<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receipt_settings')) {
            return;
        }

        Schema::table('receipt_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('receipt_settings', 'direct_print_after_payment')) {
                $table->boolean('direct_print_after_payment')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('receipt_settings')) {
            return;
        }

        Schema::table('receipt_settings', function (Blueprint $table) {
            if (Schema::hasColumn('receipt_settings', 'direct_print_after_payment')) {
                $table->dropColumn('direct_print_after_payment');
            }
        });
    }
};
