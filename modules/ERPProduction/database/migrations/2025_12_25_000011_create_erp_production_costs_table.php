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
        Schema::create('erp_production_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->unique()->constrained('erp_production_orders')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity_produced', 10, 2);
            $table->decimal('theoretical_unit_cost', 12, 2); // Coût BOM
            $table->decimal('actual_unit_cost', 12, 2); // Coût réel
            $table->decimal('total_actual_cost', 12, 2); // Coût total réel
            $table->decimal('cost_variance', 12, 2); // Écart (réel - théorique)
            $table->decimal('yield_rate', 5, 2); // Rendement %
            $table->dateTime('calculated_at');
            $table->timestamps();
            
            // Index
            $table->index('product_id');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_production_costs');
    }
};
