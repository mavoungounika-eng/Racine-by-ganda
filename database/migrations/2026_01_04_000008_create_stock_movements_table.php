<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * CRITICAL: This table is the SINGLE SOURCE OF TRUTH for stock.
     * All stock calculations are derived from movements, never from cached totals.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            
            // Material Reference
            $table->string('material_type')->comment('fabric, thread, button, accessory, etc.');
            $table->unsignedBigInteger('material_id')->nullable()->comment('FK to specific material table if exists');
            $table->string('material_reference')->comment('SKU, roll number, or unique identifier');
            
            // Movement Details
            $table->decimal('quantity', 10, 3)->comment('Quantity moved (always positive)');
            $table->string('unit', 10)->comment('m, kg, pcs, etc.');
            $table->enum('direction', ['IN', 'OUT'])->comment('IN = stock increase, OUT = stock decrease');
            
            // Source Traceability (WHY this movement happened)
            $table->string('source_type')->comment('PURCHASE, PRODUCTION, ADJUSTMENT, RETURN, INITIAL');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Production Order ID, Purchase Order ID, etc.');
            
            // Valuation (for IN movements - cost tracking)
            $table->decimal('unit_cost', 10, 2)->nullable()->comment('Cost per unit for IN movements');
            $table->decimal('total_value', 10, 2)->nullable()->comment('Total value = quantity * unit_cost');
            
            // Accountability (WHO made this movement)
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('User who recorded the movement');
            $table->text('notes')->nullable();
            
            // Timestamp (WHEN this movement happened)
            $table->timestamp('movement_date')->useCurrent()->comment('When the physical movement occurred');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['material_type', 'material_reference'], 'idx_material_lookup');
            $table->index(['source_type', 'source_id'], 'idx_source_traceability');
            $table->index('movement_date', 'idx_movement_date');
            $table->index('direction', 'idx_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
