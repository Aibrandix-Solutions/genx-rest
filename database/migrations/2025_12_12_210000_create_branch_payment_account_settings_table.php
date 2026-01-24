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
        Schema::create('branch_payment_account_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('payment_method'); // cash, upi, card, stripe, razorpay, etc.
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            // Removed foreign key to payment_accounts - it exists in Inventory module and may not be available
            
            // Ensure one default account per payment method per branch
            $table->unique(['branch_id', 'payment_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_payment_account_settings');
    }
};

