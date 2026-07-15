<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['menus', 'item_categories', 'menu_items'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $indexName = $tableName.'_branch_catalog_group_unique';

            if (! Schema::hasColumn($tableName, 'catalog_group_uuid')) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->uuid('catalog_group_uuid')->nullable()->after('branch_id');
                    $table->unique(['branch_id', 'catalog_group_uuid'], $indexName);
                });

                continue;
            }

            $legacyIndex = $tableName.'_catalog_group_uuid_index';

            if (Schema::hasIndex($tableName, $legacyIndex)) {
                Schema::table($tableName, function (Blueprint $table) use ($legacyIndex) {
                    $table->dropIndex($legacyIndex);
                });
            }

            if (! Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->unique(['branch_id', 'catalog_group_uuid'], $indexName);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['menus', 'item_categories', 'menu_items'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'catalog_group_uuid')) {
                continue;
            }

            $indexName = $tableName.'_branch_catalog_group_unique';

            if (Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('catalog_group_uuid');
            });
        }
    }
};
