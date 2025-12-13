<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_transactions')) {
            Schema::create('account_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_account_id');
                $table->decimal('amount', 16, 2);
                $table->enum('type', ['credit', 'debit']); // Credit = Money Out (Spend), Debit = Money In (Income) - accounting standard for Asset accounts is Debit increase, Credit decrease. 
                // Wait, for a Bank Account (Asset):
                // Deposit = Debit (+), Withdrawal = Credit (-)
                // But users often think: Credit my account = Add money. 
                // Let's stick to strict Accounting: Debit adds to Asset, Credit subtracts.
                // Actually, for clarity, I'll use 'income' and 'expense' or 'in' and 'out' in code logic, but 'credit'/'debit' in DB is fine if documented.
                // Let's use: credit (decrease balance), debit (increase balance).
                
                $table->nullableMorphs('reference'); // reference_type, reference_id (SupplierPayment, Expense, Order)
                $table->string('description')->nullable();
                $table->dateTime('transaction_date');
                $table->timestamps();

                $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
