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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('reward_point_discount', 16, 2)->nullable()->default(null)->after('discount_amount');
            $table->integer('reward_points_redeemed')->nullable()->default(null)->after('reward_point_discount');
            $table->integer('reward_points_earned')->nullable()->default(null)->after('reward_points_redeemed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['reward_point_discount', 'reward_points_redeemed', 'reward_points_earned']);
        });
    }
};
