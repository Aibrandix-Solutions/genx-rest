<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_restaurant_settlement_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_payment_id')->constrained(table: 'hotel_restaurant_settlement_payments', indexName: 'hrsa_payment_fk')->cascadeOnDelete();
            $table->foreignId('restaurant_order_id')->constrained(table: 'orders', indexName: 'hrsa_order_fk')->cascadeOnDelete();
            $table->foreignId('restaurant_branch_id')->constrained(table: 'branches', indexName: 'hrsa_rest_branch_fk')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained(table: 'hotel_reservations', indexName: 'hrsa_reservation_fk')->cascadeOnDelete();
            $table->decimal('applied_amount', 16, 2);
            $table->decimal('remaining_amount', 16, 2)->default(0);
            $table->timestamp('allocated_at');
            $table->foreignId('allocated_by_user_id')->nullable()->constrained(table: 'users', indexName: 'hrsa_alloc_by_fk')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_id', 'allocated_at'], 'hrsa_resv_allocated_idx');
            $table->index(['restaurant_order_id', 'allocated_at'], 'hrsa_order_allocated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_restaurant_settlement_allocations');
    }
};
