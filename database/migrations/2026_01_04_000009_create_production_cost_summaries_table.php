<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * CRITICAL: This table stores the IMMUTABLE cost summary for each completed production order.
     * Generated ONCE at order closure, never modified (audit trail).
     */
    public function up(): void
    {
        Schema::create('production_cost_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->unique()->constrained('production_orders')->cascadeOnDelete();
            
            // Real Costs (from actual consumption)
            $table->decimal('material_cost_real', 10, 2)->comment('Actual material cost from consumption');
            $table->decimal('labor_cost_real', 10, 2)->comment('Actual labor cost from time logs');
            $table->decimal('overhead_cost', 10, 2)->default(0)->comment('Allocated overhead (future)');
            $table->decimal('total_cost', 10, 2)->comment('Total production cost');
            
            // Unit Costs (for stock valuation)
            $table->decimal('unit_cost_good', 10, 2)->comment('Cost per 1st choice unit');
            $table->decimal('unit_cost_second', 10, 2)->nullable()->comment('Cost per 2nd choice unit (if different)');
            
            // Variance Analysis (Real vs Standard)
            $table->decimal('standard_cost', 10, 2)->nullable()->comment('From BOM snapshot');
            $table->decimal('variance', 10, 2)->nullable()->comment('Real - Standard');
            $table->decimal('variance_percentage', 5, 2)->nullable()->comment('(Variance / Standard) * 100');
            
            // Quantities (snapshot for audit)
            $table->integer('qty_good')->comment('1st choice quantity');
            $table->integer('qty_second')->default(0)->comment('2nd choice quantity');
            $table->integer('qty_rejected')->default(0)->comment('Rejected quantity');
            
            // Metadata
            $table->string('bom_version')->nullable()->comment('BOM version used');
            $table->timestamp('calculated_at')->useCurrent()->comment('When cost was calculated');
            
            $table->timestamps();
            
            // Index for reporting
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cost_summaries');
    }
};
