<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference')->unique();
            $table->string('unit'); // m, kg, l, unit
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('min_stock_alert', 10, 2)->default(10);
            $table->decimal('unit_price', 10, 2)->nullable(); // Prix moyen pondéré
            $table->foreignId('supplier_id')->nullable()->constrained('erp_suppliers')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_raw_materials');
    }
};
