<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_settings')) {
            return;
        }

        Schema::create('hrm_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->string('setting_type')->default('text'); // text, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->unique(['restaurant_id', 'setting_key']);
        });

        // Insert default EPF/ETF settings
        DB::table('hrm_settings')->insert([
            [
                'restaurant_id' => 1, // Will be updated per restaurant
                'setting_key' => 'epf_basic_salary',
                'setting_value' => '0',
                'setting_type' => 'number',
                'description' => 'Standard basic salary for EPF calculation (same for all employees)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'setting_key' => 'etf_basic_salary',
                'setting_value' => '0',
                'setting_type' => 'number',
                'description' => 'Standard basic salary for ETF calculation (same for all employees)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'setting_key' => 'epf_employee_rate',
                'setting_value' => '8',
                'setting_type' => 'number',
                'description' => 'Employee EPF contribution rate (%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'setting_key' => 'epf_employer_rate',
                'setting_value' => '12',
                'setting_type' => 'number',
                'description' => 'Employer EPF contribution rate (%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'setting_key' => 'etf_employer_rate',
                'setting_value' => '3',
                'setting_type' => 'number',
                'description' => 'Employer ETF contribution rate (%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'setting_key' => 'epf_auto_calculate',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'Auto-calculate EPF based on standard basic salary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'setting_key' => 'etf_auto_calculate',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'Auto-calculate ETF based on standard basic salary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_settings');
    }
};
