<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('cost_price', 10, 2)->nullable(); // Prix de revient
            $table->decimal('weight', 8, 3)->nullable(); // kg
            $table->json('dimensions')->nullable(); // {L, l, h}
            $table->foreignId('supplier_id')->nullable()->constrained('erp_suppliers')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_product_details');
    }
};
