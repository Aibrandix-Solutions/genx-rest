<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance Payment Accounts Table
        Schema::table('payment_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_accounts', 'account_number')) {
                $table->string('account_number')->nullable()->after('name');
            }
            if (!Schema::hasColumn('payment_accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('branch_id');
            }
            if (!Schema::hasColumn('payment_accounts', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });

        // 2. Link Expenses to Payment Accounts
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'payment_account_id')) {
                $table->unsignedBigInteger('payment_account_id')->nullable()->after('payment_method');
                $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('set null');
            }
        });

        // 3. Link Order Payments to Payment Accounts
        // Note: The main 'payments' table handles cash/card payments for orders.
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_account_id')) {
                $table->unsignedBigInteger('payment_account_id')->nullable()->after('payment_method');
                $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropColumn('payment_account_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropColumn('payment_account_id');
        });

        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'is_active', 'description']);
        });
    }
};
