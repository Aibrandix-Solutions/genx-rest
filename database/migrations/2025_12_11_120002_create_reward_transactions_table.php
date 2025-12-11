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
        Schema::create('reward_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            
            $table->enum('type', ['earn', 'redeem', 'adjust', 'expire', 'release'])->index();
            $table->integer('points'); // Positive for earn/adjust, negative for redeem/expire
            $table->decimal('amount_value', 16, 2)->nullable(); // Order amount that earned points or discount amount from redemption
            $table->text('description')->nullable();
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamp('expires_at')->nullable(); // For earned points
            $table->unsignedBigInteger('created_by')->nullable(); // User who created adjustment
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index(['customer_id', 'restaurant_id']);
            $table->index(['type', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_transactions');
    }
};

