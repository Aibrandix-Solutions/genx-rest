<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_holidays')) {
            return;
        }

        Schema::create('hrm_holidays', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // null = global holiday

            $table->date('date');
            $table->string('name');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id', 'date'], 'hrm_holidays_restaurant_branch_date_idx');
            $table->unique(['restaurant_id', 'branch_id', 'date', 'name'], 'hrm_holidays_unique');

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_holidays');
    }
};
