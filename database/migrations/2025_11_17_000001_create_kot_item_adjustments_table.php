<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kot_item_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('order_number')->nullable();
            $table->string('formatted_order_number')->nullable();
            $table->string('table_code')->nullable();
            $table->unsignedBigInteger('kot_id')->nullable();
            $table->unsignedBigInteger('kot_item_id')->nullable();
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->unsignedBigInteger('menu_item_variation_id')->nullable();
            $table->string('menu_item_name')->nullable();
            $table->string('menu_item_variation_name')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('performed_by_name')->nullable();
            $table->string('action');
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_after')->nullable();
            $table->text('note');
            $table->timestamps();

            // Indexes
            $table->index(['restaurant_id', 'branch_id'], 'kot_adjustments_restaurant_branch_index');
            $table->index('action', 'kot_adjustments_action_index');

            // Foreign Keys
            // Using nullOnDelete to preserve audit logs even if the original record is deleted
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('kot_id')->references('id')->on('kots')->nullOnDelete();
            $table->foreign('kot_item_id')->references('id')->on('kot_items')->nullOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kot_item_adjustments');
    }
};
