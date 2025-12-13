<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Suppliers Table
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('address');
            }
            if (!Schema::hasColumn('suppliers', 'note')) {
                $table->text('note')->nullable()->after('is_active');
            }
        });

        // 2. Payment Accounts (New Module Requirement)
        if (!Schema::hasTable('payment_accounts')) {
            Schema::create('payment_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., 'Cash Drawer', 'Bank Account'
                $table->string('type')->default('cash'); // cash, bank, etc.
                $table->decimal('current_balance', 16, 2)->default(0);
                $table->unsignedBigInteger('branch_id')->nullable(); // Optional if branch-specific
                $table->timestamps();
            });
        }

        // 3. Supplier Payments
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id');
                $table->unsignedBigInteger('payment_account_id')->nullable(); // Linked to the new account system
                $table->decimal('amount', 16, 2);
                $table->dateTime('paid_on');
                $table->string('payment_method'); // cash, card, etc.
                $table->string('transaction_id')->nullable();
                $table->text('note')->nullable();
                $table->string('document_path')->nullable(); // For payment proof
                $table->unsignedBigInteger('added_by'); // User who recorded it
                $table->timestamps();

                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
                $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('set null');
                $table->foreign('added_by')->references('id')->on('users');
            });
        }

        // 4. Supplier Documents (General documents, distinct from payment proofs)
        if (!Schema::hasTable('supplier_documents')) {
            Schema::create('supplier_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id');
                $table->string('name');
                $table->string('file_path');
                $table->string('file_type')->nullable(); // pdf, doc, etc.
                $table->unsignedBigInteger('uploaded_by');
                $table->timestamps();

                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
                $table->foreign('uploaded_by')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('payment_accounts');
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'note']);
        });
    }
};

