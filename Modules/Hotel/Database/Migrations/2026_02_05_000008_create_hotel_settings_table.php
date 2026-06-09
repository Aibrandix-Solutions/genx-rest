<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            
            // Check-in/out policies
            $table->time('default_check_in_time')->default('14:00');
            $table->time('default_checkout_time')->default('12:00');
            $table->decimal('early_checkin_charge_per_hour', 10, 2)->default(0);
            $table->decimal('late_checkout_charge_per_hour', 10, 2)->default(0);
            
            // Payment policies
            $table->enum('payment_policy', [
                'full_advance',
                'partial_deposit',
                'pay_at_checkout'
            ])->default('pay_at_checkout');
            $table->decimal('deposit_percentage', 5, 2)->nullable(); // For partial_deposit
            $table->text('cancellation_policy')->nullable();
            
            // Features
            $table->boolean('enable_room_service')->default(true);
            $table->boolean('enable_housekeeping_module')->default(true);
            $table->boolean('enable_dynamic_pricing')->default(false);
            
            // Tax & charges
            $table->decimal('tax_rate', 5, 2)->default(0); // Percentage
            $table->decimal('service_charge_rate', 5, 2)->default(0); // Percentage
            
            $table->timestamps();

            $table->unique('branch_id'); // One setting per branch
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_settings');
    }
};
