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
        Schema::create('hotel_room_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('hotel_reservations')->onDelete('cascade');
            $table->enum('charge_type', [
                'room_night',
                'restaurant',
                'minibar',
                'laundry',
                'service',
                'tax',
                'other'
            ]);
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null'); // For restaurant orders
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->date('charge_date');
            $table->timestamps();

            $table->index('reservation_id');
            $table->index(['charge_date', 'charge_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_room_charges');
    }
};
