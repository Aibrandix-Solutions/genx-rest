<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add discount fields
            $table->decimal('discount', 10, 2)->default(0)->after('total_amount')->comment('Discount amount (fixed or %)');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed')->after('discount')->comment('Type of discount applied');
            
            // Add location field
            $table->unsignedBigInteger('location_id')->nullable()->after('branch_id');
            $table->foreign('location_id')->references('id')->on('purchase_locations')->onDelete('set null');
            
            // Change status enum to simpler workflow: ordered, pending, received, cancelled
            // Note: We'll modify the column after data migration
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['discount', 'discount_type', 'location_id']);
        });
    }
};
