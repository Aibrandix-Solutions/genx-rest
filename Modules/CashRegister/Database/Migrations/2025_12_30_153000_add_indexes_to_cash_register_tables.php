<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_register_sessions')) {
            $this->addIndexIfMissing(
                'cash_register_sessions',
                'cash_register_sessions_restaurant_opened_at_idx',
                ['restaurant_id', 'opened_at']
            );

            $this->addIndexIfMissing(
                'cash_register_sessions',
                'cash_register_sessions_rest_branch_opened_at_idx',
                ['restaurant_id', 'branch_id', 'opened_at']
            );

            $this->addIndexIfMissing(
                'cash_register_sessions',
                'cash_register_sessions_openedby_status_openedat_idx',
                ['opened_by', 'status', 'opened_at']
            );
        }

        if (Schema::hasTable('cash_register_transactions')) {
            $this->addIndexIfMissing(
                'cash_register_transactions',
                'cash_register_transactions_session_type_idx',
                ['cash_register_session_id', 'type']
            );

            $this->addIndexIfMissing(
                'cash_register_transactions',
                'cash_register_transactions_happened_at_idx',
                ['happened_at']
            );

            $this->addIndexIfMissing(
                'cash_register_transactions',
                'cash_register_transactions_rest_branch_happened_at_idx',
                ['restaurant_id', 'branch_id', 'happened_at']
            );
        }

        if (Schema::hasTable('cash_register_counts')) {
            $this->addIndexIfMissing(
                'cash_register_counts',
                'cash_register_counts_session_id_idx',
                ['cash_register_session_id']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_register_sessions')) {
            $this->dropIndexIfExists('cash_register_sessions', 'cash_register_sessions_restaurant_opened_at_idx');
            $this->dropIndexIfExists('cash_register_sessions', 'cash_register_sessions_rest_branch_opened_at_idx');
            $this->dropIndexIfExists('cash_register_sessions', 'cash_register_sessions_openedby_status_openedat_idx');
        }

        if (Schema::hasTable('cash_register_transactions')) {
            $this->dropIndexIfExists('cash_register_transactions', 'cash_register_transactions_session_type_idx');
            $this->dropIndexIfExists('cash_register_transactions_happened_at_idx');
            $this->dropIndexIfExists('cash_register_transactions_rest_branch_happened_at_idx');
        }

        if (Schema::hasTable('cash_register_counts')) {
            $this->dropIndexIfExists('cash_register_counts', 'cash_register_counts_session_id_idx');
        }
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        $existing = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        if (!empty($existing)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $existing = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        if (empty($existing)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};
