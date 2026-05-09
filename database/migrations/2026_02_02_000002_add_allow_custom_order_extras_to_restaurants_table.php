<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'allow_custom_order_extras')) {
                $table->boolean('allow_custom_order_extras')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'allow_custom_order_extras')) {
                $table->dropColumn('allow_custom_order_extras');
            }
        });
    }
};
