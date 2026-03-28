<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kot_items', 'combo_pack_id')) {
            Schema::table('kot_items', function (Blueprint $table) {
                $table->unsignedBigInteger('combo_pack_id')->nullable()->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kot_items', 'combo_pack_id')) {
            Schema::table('kot_items', function (Blueprint $table) {
                $table->dropColumn('combo_pack_id');
            });
        }
    }
};
