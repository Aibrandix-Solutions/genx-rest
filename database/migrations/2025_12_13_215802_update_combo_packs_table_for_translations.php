<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns are not already JSON
        if (Schema::hasColumn('combo_packs', 'name') && 
            DB::connection()->getSchemaBuilder()->getColumnType('combo_packs', 'name') !== 'json') {
            
            // Add temporary JSON columns
            Schema::table('combo_packs', function (Blueprint $table) {
                $table->json('name_json')->nullable()->after('name');
                $table->json('description_json')->nullable()->after('description');
            });

            // Copy existing values into JSON format with current locale
            $locale = app()->getLocale() ?: 'en';
            DB::statement("UPDATE combo_packs SET name_json = JSON_OBJECT('$locale', name) WHERE name IS NOT NULL");
            DB::statement("UPDATE combo_packs SET description_json = JSON_OBJECT('$locale', description) WHERE description IS NOT NULL");

            // Drop old columns
            Schema::table('combo_packs', function (Blueprint $table) {
                $table->dropColumn('name');
                $table->dropColumn('description');
            });

            // Rename new columns
            Schema::table('combo_packs', function (Blueprint $table) {
                $table->renameColumn('name_json', 'name');
                $table->renameColumn('description_json', 'description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON back to string/text if needed
        if (Schema::hasColumn('combo_packs', 'name') && 
            DB::connection()->getSchemaBuilder()->getColumnType('combo_packs', 'name') === 'json') {
            
            Schema::table('combo_packs', function (Blueprint $table) {
                $table->string('name_str')->nullable()->after('name');
                $table->text('description_str')->nullable()->after('description');
            });

            $locale = app()->getLocale() ?: 'en';
            DB::statement("UPDATE combo_packs SET name_str = JSON_UNQUOTE(JSON_EXTRACT(name, '$.$locale'))");
            DB::statement("UPDATE combo_packs SET description_str = JSON_UNQUOTE(JSON_EXTRACT(description, '$.$locale'))");

            Schema::table('combo_packs', function (Blueprint $table) {
                $table->dropColumn('name');
                $table->dropColumn('description');
            });

            Schema::table('combo_packs', function (Blueprint $table) {
                $table->renameColumn('name_str', 'name');
                $table->renameColumn('description_str', 'description');
            });
        }
    }
};
