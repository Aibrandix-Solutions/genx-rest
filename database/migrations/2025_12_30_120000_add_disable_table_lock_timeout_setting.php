<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('disable_table_lock_timeout')
                ->default(false)
                ->after('table_lock_timeout_minutes')
                ->comment('If true, manual table locks do not expire by time');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('disable_table_lock_timeout');
        });
    }
};
