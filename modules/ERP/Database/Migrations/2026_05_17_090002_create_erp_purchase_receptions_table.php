<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_purchase_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('erp_purchases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('reception_date');
            $table->string('bl_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('complete'); // complete, partial, refused
            $table->timestamps();
        });

        Schema::create('erp_purchase_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('erp_purchase_receptions')->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained('erp_purchase_items')->cascadeOnDelete();
            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('quantity_received', 10, 2);
            $table->decimal('quantity_refused', 10, 2)->default(0);
            $table->decimal('unit_price_received', 10, 2);
            $table->string('refuse_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_purchase_reception_items');
        Schema::dropIfExists('erp_purchase_receptions');
    }
};
