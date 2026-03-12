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
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('table_id');
            $table->boolean('locked_by_order')->default(false)->after('order_id')
                ->comment('True if lock is tied to an active order');
            
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'locked_by_order']);
        });
    }
};
