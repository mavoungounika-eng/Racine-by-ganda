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
        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            
            // Variant identification (size/color/etc.)
            $table->string('variant_sku')->comment('Exact SKU produced (e.g., CHEM-BLEU-M)');
            $table->json('variant_attributes')->nullable()->comment('Size, Color, etc. as structured data');
            
            // Quantities by quality grade
            $table->integer('qty_good')->default(0)->comment('First choice - sellable stock');
            $table->integer('qty_second')->default(0)->comment('Second choice - outlet/discount');
            $table->integer('qty_rejected')->default(0)->comment('Waste - non-sellable');
            
            $table->timestamps();
            
            // Business constraint: one output record per variant per OF
            $table->unique(['production_order_id', 'variant_sku'], 'unique_of_variant');
            
            // Indexes for reporting
            $table->index(['product_id', 'variant_sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
    }
};
