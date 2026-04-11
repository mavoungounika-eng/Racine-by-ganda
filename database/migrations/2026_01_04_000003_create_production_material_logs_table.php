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
        Schema::create('production_material_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            
            // Material Reference (could be fabric roll, accessories, etc.)
            $table->string('material_type')->comment('Type: fabric, thread, button, etc.');
            $table->unsignedBigInteger('material_id')->nullable()->comment('Reference to stock item/roll');
            $table->string('material_reference')->nullable()->comment('Manual reference if no stock tracking');
            
            // Consumption
            $table->decimal('quantity_used', 10, 3)->comment('Actual quantity consumed');
            $table->string('unit', 10)->comment('Unit: m, kg, pcs');
            
            // Efficiency tracking (for fabric cutting)
            $table->decimal('marker_efficiency', 5, 2)->nullable()->comment('Cutting efficiency percentage');
            $table->decimal('waste_quantity', 10, 3)->nullable()->comment('Waste/offcuts');
            
            // Traceability
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at')->useCurrent();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['production_order_id', 'material_type']);
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_material_logs');
    }
};
