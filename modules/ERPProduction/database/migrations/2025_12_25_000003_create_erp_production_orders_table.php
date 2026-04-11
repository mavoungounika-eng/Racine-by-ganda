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
        Schema::create('erp_production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('bom_id')->nullable()->constrained('erp_boms')->onDelete('restrict');
            $table->decimal('quantity_planned', 10, 2);
            $table->decimal('quantity_produced', 10, 2)->default(0.00);
            $table->decimal('quantity_rejected', 10, 2)->default(0.00);
            $table->enum('status', ['draft', 'planned', 'in_progress', 'finished', 'closed', 'cancelled'])->default('draft');
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->dateTime('actual_start_date')->nullable();
            $table->dateTime('actual_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('status');
            $table->index('planned_start_date');
            $table->index('actual_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_production_orders');
    }
};
