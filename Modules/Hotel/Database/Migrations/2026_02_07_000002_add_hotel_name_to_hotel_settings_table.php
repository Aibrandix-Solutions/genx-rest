<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('hotel_settings', function (Blueprint $table) {
            $table->string('hotel_name')->nullable()->after('branch_id');
        });
    }

    public function down()
    {
        Schema::table('hotel_settings', function (Blueprint $table) {
            $table->dropColumn('hotel_name');
        });
    }
};
