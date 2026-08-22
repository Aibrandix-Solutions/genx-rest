<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_expense_payments')) {
            Schema::create('hotel_expense_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('hotel_expense_id')->index();
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash');
                $table->string('reference_number')->nullable();
                $table->dateTime('paid_at');
                $table->text('notes')->nullable();
                $table->string('receipt_path')->nullable();
                $table->unsignedBigInteger('paid_by_user_id')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('hotel_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_expenses', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('hotel_expenses', 'amount_paid')) {
                $table->decimal('amount_paid', 12, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('hotel_expenses', 'balance_due')) {
                $table->decimal('balance_due', 12, 2)->default(0)->after('amount_paid');
            }
            if (!Schema::hasColumn('hotel_expenses', 'due_date')) {
                $table->date('due_date')->nullable()->after('expense_date');
            }
        });

        // Keep compatibility with existing enum column while adding partial state.
        DB::statement("ALTER TABLE hotel_expenses MODIFY COLUMN status ENUM('paid','pending','partial','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('hotel_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_expenses', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('hotel_expenses', 'balance_due')) {
                $table->dropColumn('balance_due');
            }
            if (Schema::hasColumn('hotel_expenses', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
            if (Schema::hasColumn('hotel_expenses', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });

        DB::statement("ALTER TABLE hotel_expenses MODIFY COLUMN status ENUM('paid','pending','cancelled') DEFAULT 'paid'");

        Schema::dropIfExists('hotel_expense_payments');
    }
};
