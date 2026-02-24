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
        // Adding indexes for performance (Epic E4)
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id', 'idx_perf_orders_user_id');
            $table->index('status', 'idx_perf_orders_status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('user_id', 'idx_perf_products_user_id');
            $table->index('category_id', 'idx_perf_products_category_id');
            $table->index('is_active', 'idx_perf_products_is_active');
            $table->index(['product_type', 'is_active'], 'idx_perf_products_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_perf_products_type_active');
            $table->dropIndex('idx_perf_products_is_active');
            $table->dropIndex('idx_perf_products_category_id');
            $table->dropIndex('idx_perf_products_user_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_perf_orders_status');
            $table->dropIndex('idx_perf_orders_user_id');
        });
    }
};
