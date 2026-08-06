<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('printers') && !Schema::hasColumn('printers', 'printer_name')) {
            Schema::table('printers', function (Blueprint $table) {
                $table->string('printer_name')->nullable()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('printers') && Schema::hasColumn('printers', 'printer_name')) {
            Schema::table('printers', function (Blueprint $table) {
                $table->dropColumn('printer_name');
            });
        }
    }
};
