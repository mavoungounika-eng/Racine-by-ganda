<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable'); // Product or RawMaterial
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
            $table->decimal('quantity', 10, 2);
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('reason')->nullable(); // Sale, Purchase, Loss, etc.
            $table->string('reference_type')->nullable(); // Order, Purchase, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->constrained('users'); // Operator
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_stock_movements');
    }
};
