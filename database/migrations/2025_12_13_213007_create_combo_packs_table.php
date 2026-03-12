<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('combo_packs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('regular_price', 16, 2)->default(0);
            $table->decimal('discounted_price', 16, 2)->default(0);
            $table->decimal('discount_amount', 16, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index(['branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_packs');
    }
};
