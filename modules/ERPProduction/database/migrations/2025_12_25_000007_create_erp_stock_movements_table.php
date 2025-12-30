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
        Schema::create('erp_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained('erp_raw_materials')->onDelete('restrict');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict');
            $table->foreignId('production_order_id')->nullable()->constrained('erp_production_orders')->onDelete('restrict');
            $table->enum('type', ['in', 'out']);
            $table->enum('source', ['raw', 'wip', 'finished']); // Matière première, En-cours, Produit fini
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 12, 2); // Coût unitaire
            $table->decimal('total_cost', 12, 2); // Quantité × coût
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index
            $table->index('material_id');
            $table->index('product_id');
            $table->index('production_order_id');
            $table->index('type');
            $table->index('source');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_stock_movements');
    }
};
