<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * CRITICAL: Remove aggregated quantity columns from production_orders.
     * These are lies - production is per variant, not per batch.
     * Truth now lives in production_outputs table.
     */
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'produced_qty_good',
                'produced_qty_second',
                'rejected_qty',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->integer('produced_qty_good')->default(0)->after('target_quantity');
            $table->integer('produced_qty_second')->default(0)->after('produced_qty_good');
            $table->integer('rejected_qty')->default(0)->after('produced_qty_second');
        });
    }
};
