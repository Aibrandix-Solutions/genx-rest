<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_expenses')) {
            return;
        }

        if (!Schema::hasColumn('hotel_expenses', 'total_amount')) {
            return;
        }

        // Seed billed total from legacy amount.
        DB::table('hotel_expenses')
            ->whereNull('total_amount')
            ->update([
                'total_amount' => DB::raw('amount'),
            ]);

        // Paid legacy rows with no payment ledger yet: treat full amount as paid.
        DB::table('hotel_expenses')
            ->where('status', 'paid')
            ->where(function ($q) {
                $q->whereNull('amount_paid')->orWhere('amount_paid', 0);
            })
            ->update([
                'amount_paid' => DB::raw('COALESCE(total_amount, amount)'),
                'balance_due' => 0,
            ]);

        // Pending / partial / other open rows: outstanding = billed - paid.
        DB::table('hotel_expenses')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'paid')
            ->update([
                'amount_paid' => DB::raw('COALESCE(amount_paid, 0)'),
                'balance_due' => DB::raw('GREATEST(COALESCE(total_amount, amount) - COALESCE(amount_paid, 0), 0)'),
            ]);

        // Cancelled: no outstanding balance.
        DB::table('hotel_expenses')
            ->where('status', 'cancelled')
            ->update([
                'balance_due' => 0,
            ]);
    }

    public function down(): void
    {
        // Irreversible data backfill.
    }
};
