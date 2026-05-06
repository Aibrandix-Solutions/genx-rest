<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Module::firstOrCreate(['name' => 'Reward Point']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('name', 'Reward Point')->delete();
    }
};
