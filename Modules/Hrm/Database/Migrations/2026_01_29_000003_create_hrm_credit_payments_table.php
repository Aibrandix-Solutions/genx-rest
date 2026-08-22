<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_credit_payments')) {
            return;
        }

        Schema::create('hrm_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('credit_purchase_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash'); // cash, salary_deduction, check, bank_transfer
            $table->string('reference_number')->nullable(); // Cheque no, receipt no
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('credit_purchase_id')->references('id')->on('hrm_credit_purchases')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hrm_employees')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['credit_purchase_id', 'payment_date']);
            $table->index(['employee_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_credit_payments');
    }
};
