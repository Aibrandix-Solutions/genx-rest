<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_account_id')->nullable(); // Null for Deposit (money from external)
            $table->unsignedBigInteger('to_account_id')->nullable(); // Null for Withdrawal? Usually not null for transfer.
            $table->decimal('amount', 16, 2);
            $table->dateTime('transfer_date');
            $table->string('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->timestamps();

            $table->foreign('from_account_id')->references('id')->on('payment_accounts')->onDelete('cascade');
            $table->foreign('to_account_id')->references('id')->on('payment_accounts')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
