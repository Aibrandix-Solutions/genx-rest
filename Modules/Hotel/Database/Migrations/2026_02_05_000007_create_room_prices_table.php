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
        Schema::create('hotel_room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->onDelete('cascade');
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('price', 10, 2);
            $table->string('reason')->nullable(); // weekend, holiday, event, seasonal
            $table->timestamps();

            $table->index('room_type_id');
            $table->index(['date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_room_prices');
    }
};
