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
        Schema::table('hotel_guests', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            // Assuming branches table exists, but we won't add constraint yet to avoid issues if table name differs
            // Just adding index for performance
            $table->index('branch_id');
        });
        
        // Populate branch_id for existing guests if any (default to current user logic is hard in migration, so nullable is fine)
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotel_guests', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
