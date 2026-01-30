<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_credit_purchases')) {
            return;
        }

        Schema::create('hrm_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('purchase_date');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending, partial, paid
            $table->boolean('auto_deduct_from_salary')->default(false);
            $table->decimal('auto_deduct_amount', 12, 2)->nullable();
            $table->string('category')->nullable(); // food, uniform, advance, tools, etc
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hrm_employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['restaurant_id', 'employee_id']);
            $table->index('status');
            $table->index('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_credit_purchases');
    }
};
