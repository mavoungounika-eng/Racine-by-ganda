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
        Schema::create('erp_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained('erp_raw_materials')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->enum('stock_type', ['raw', 'wip', 'finished']); // Type de stock
            $table->decimal('quantity', 10, 2)->default(0.00); // Stock actuel
            $table->decimal('average_cost', 12, 2)->default(0.00); // Coût moyen pondéré (CMP)
            $table->decimal('total_value', 12, 2)->default(0.00); // Valorisation totale
            $table->timestamps();
            
            // Contraintes
            $table->unique(['material_id', 'stock_type']);
            $table->unique(['product_id', 'stock_type']);
            
            // Index
            $table->index('stock_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_stock_balances');
    }
};
