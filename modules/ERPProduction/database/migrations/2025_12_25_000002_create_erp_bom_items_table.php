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
        Schema::create('erp_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('erp_boms')->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained('erp_raw_materials')->onDelete('restrict');
            $table->decimal('quantity', 10, 4); // Quantité nécessaire (précision 4 décimales)
            $table->string('unit', 20); // meter, kg, unit, bobine
            $table->decimal('waste_percentage', 5, 2)->default(0.00); // % perte matière (0-100)
            $table->integer('sequence')->default(0); // Ordre de fabrication/assemblage
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('bom_id');
            $table->index('raw_material_id');
            $table->index('sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_bom_items');
    }
};
