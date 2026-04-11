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
        if (!Schema::hasTable('erp_stock_movements')) {
            Schema::create('erp_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->morphs('stockable');
                $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
                $table->decimal('quantity', 10, 2);
                $table->string('from_location')->nullable();
                $table->string('to_location')->nullable();
                $table->string('reason')->nullable();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // ERPProduction extension columns.
                $table->foreignId('material_id')->nullable()->constrained('erp_raw_materials')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('production_order_id')->nullable()->constrained('erp_production_orders')->nullOnDelete();
                $table->enum('source', ['raw', 'wip', 'finished'])->nullable();
                $table->decimal('unit_cost', 12, 2)->nullable();
                $table->decimal('total_cost', 12, 2)->nullable();
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('material_id');
                $table->index('product_id');
                $table->index('production_order_id');
                $table->index('source');
                $table->index('created_at');
            });

            return;
        }

        Schema::table('erp_stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('erp_stock_movements', 'material_id')) {
                $table->foreignId('material_id')->nullable()->constrained('erp_raw_materials')->nullOnDelete();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'product_id')) {
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'production_order_id')) {
                $table->foreignId('production_order_id')->nullable()->constrained('erp_production_orders')->nullOnDelete();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'source')) {
                $table->enum('source', ['raw', 'wip', 'finished'])->nullable();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('erp_stock_movements', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: this migration extends a shared table created by ERP.
    }
};
