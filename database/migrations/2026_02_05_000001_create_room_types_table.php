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
        Schema::create('hotel_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->integer('max_occupancy')->default(2);
            $table->decimal('extra_bed_charge', 10, 2)->default(0);
            $table->decimal('extra_person_charge', 10, 2)->default(0);
            $table->json('amenities')->nullable(); // ['wifi', 'tv', 'ac', 'minibar', etc.]
            $table->json('photos')->nullable(); // Array of image paths
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_room_types');
    }
};
