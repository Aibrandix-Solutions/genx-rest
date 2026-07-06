<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_restaurant_settlement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained(table: 'restaurants', indexName: 'hrsp_restaurant_fk')->cascadeOnDelete();
            $table->foreignId('hotel_branch_id')->constrained(table: 'branches', indexName: 'hrsp_hotel_branch_fk')->cascadeOnDelete();
            $table->foreignId('restaurant_branch_id')->constrained(table: 'branches', indexName: 'hrsp_rest_branch_fk')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained(table: 'hotel_reservations', indexName: 'hrsp_reservation_fk')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 16, 2);
            $table->string('payment_method', 50);
            $table->string('reference_number')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained(table: 'users', indexName: 'hrsp_created_by_fk')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained(table: 'users', indexName: 'hrsp_updated_by_fk')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_id', 'restaurant_branch_id'], 'hrsp_resv_rest_branch_idx');
            $table->index(['payment_date', 'restaurant_branch_id'], 'hrsp_pay_date_rest_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_restaurant_settlement_payments');
    }
};
