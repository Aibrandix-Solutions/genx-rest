<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The legacy 'guests' table is unused — all guest data lives in 'hotel_guests'.
     */
    public function up()
    {
        Schema::dropIfExists('guests');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Not recreating the dead table on rollback — data was already empty.
    }
};
