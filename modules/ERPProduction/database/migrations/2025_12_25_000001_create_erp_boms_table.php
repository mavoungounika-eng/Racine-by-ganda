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
        Schema::create('erp_boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('version', 10)->default('1.0');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1.00); // Quantité produite
            $table->string('unit', 20)->default('unit'); // unit, meter, kg
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // BOM par défaut pour ce produit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Contraintes
            $table->unique(['product_id', 'version']);
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_boms');
    }
};
