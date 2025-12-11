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
        Schema::create('reward_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            
            // General Settings
            $table->boolean('enable_reward_point')->default(false);
            $table->string('reward_point_display_name')->default('Reward');
            
            // Earning Points Settings
            $table->decimal('amount_spend_for_unit_point', 16, 2)->default(1.00);
            $table->decimal('minimum_order_total_to_earn', 16, 2)->default(1.00);
            $table->integer('maximum_points_per_order')->nullable();
            
            // Redeem Points Settings
            $table->decimal('redeem_amount_per_unit_point', 16, 2)->default(1.00);
            $table->decimal('minimum_order_total_to_redeem', 16, 2)->default(1.00);
            $table->integer('minimum_redeem_point')->nullable();
            $table->integer('maximum_redeem_point_per_order')->nullable();
            $table->integer('reward_point_expiry_period')->default(12); // in months
            
            $table->timestamps();
            
            $table->unique('restaurant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_settings');
    }
};

