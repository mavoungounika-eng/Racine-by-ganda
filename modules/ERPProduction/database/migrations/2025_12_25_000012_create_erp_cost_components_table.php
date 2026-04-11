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
        Schema::create('erp_cost_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_cost_id')->constrained('erp_production_costs')->onDelete('cascade');
            $table->enum('component_type', ['material', 'scrap', 'rework', 'overhead']);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('production_cost_id');
            $table->index('component_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_cost_components');
    }
};
