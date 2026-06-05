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
        Schema::create('hotel_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('guest_id')->constrained('hotel_guests')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('hotel_rooms')->onDelete('set null');
            $table->string('reservation_number')->unique();
            
            // Check-in/out details
            $table->date('check_in_date');
            $table->time('check_in_time')->nullable();
            $table->date('checkout_date');
            $table->time('checkout_time')->nullable();
            $table->timestamp('actual_check_in')->nullable();
            $table->timestamp('actual_checkout')->nullable();
            
            // Guest details
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->text('special_requests')->nullable();
            
            // Booking info
            $table->string('booking_source')->nullable(); // walk-in, website, phone, OTA
            $table->enum('status', [
                'confirmed',
                'checked_in',
                'checked_out',
                'cancelled',
                'no_show'
            ])->default('confirmed');
            
            // Financial
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);
            
            // Audit
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['check_in_date', 'checkout_date']);
            $table->index('guest_id');
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_reservations');
    }
};
