<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'is_employee')) {
                $table->boolean('is_employee')->default(false);
            }

            if (!Schema::hasColumn('customers', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable();
            }
        });

        if (!$this->indexExists('customers', 'customers_employee_id_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('employee_id');
            });
        }

        if (!$this->indexExists('customers', 'customers_is_employee_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('is_employee');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        if ($this->indexExists('customers', 'customers_employee_id_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('customers_employee_id_index');
            });
        }

        if ($this->indexExists('customers', 'customers_is_employee_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('customers_is_employee_index');
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'employee_id')) {
                $table->dropColumn('employee_id');
            }

            if (Schema::hasColumn('customers', 'is_employee')) {
                $table->dropColumn('is_employee');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            "SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?",
            [$database, $table, $index]
        );

        return (int) ($result->count ?? 0) > 0;
    }
};
