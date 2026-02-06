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
        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->onDelete('cascade');
            $table->string('room_number')->unique();
            $table->string('floor')->nullable();
            $table->string('section')->nullable(); // Wing A, Wing B, etc.
            $table->enum('status', [
                'available',
                'occupied',
                'cleaning',
                'maintenance',
                'reserved',
                'blocked'
            ])->default('available');
            $table->text('notes')->nullable();
            $table->timestamp('last_cleaned_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('room_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_rooms');
    }
};
