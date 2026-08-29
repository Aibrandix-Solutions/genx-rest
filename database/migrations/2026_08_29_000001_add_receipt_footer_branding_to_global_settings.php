<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('global_settings', 'show_receipt_footer')) {
                $table->boolean('show_receipt_footer')->default(false)->after('show_logo_text');
            }
            if (! Schema::hasColumn('global_settings', 'receipt_footer_text')) {
                $table->string('receipt_footer_text', 255)->nullable()->after('show_receipt_footer');
            }
            if (! Schema::hasColumn('global_settings', 'receipt_footer_phone_code')) {
                $table->string('receipt_footer_phone_code', 10)->nullable()->after('receipt_footer_text');
            }
            if (! Schema::hasColumn('global_settings', 'receipt_footer_phone')) {
                $table->string('receipt_footer_phone', 30)->nullable()->after('receipt_footer_phone_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $columns = [
                'show_receipt_footer',
                'receipt_footer_text',
                'receipt_footer_phone_code',
                'receipt_footer_phone',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('global_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
