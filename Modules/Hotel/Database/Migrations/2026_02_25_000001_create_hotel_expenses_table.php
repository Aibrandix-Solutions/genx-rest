<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->string('title');
            $table->string('department')->nullable()
                ->comment('e.g. housekeeping, front_desk, maintenance, laundry, utilities, f_and_b, other');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method')->default('cash')
                ->comment('cash, card, bank_transfer, upi, other');
            $table->string('vendor')->nullable()
                ->comment('Supplier or vendor name');
            $table->string('receipt_number')->nullable();
            $table->string('receipt_path')->nullable()
                ->comment('Uploaded receipt file path');
            $table->enum('status', ['paid', 'pending', 'cancelled'])->default('paid');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_expenses');
    }
};
