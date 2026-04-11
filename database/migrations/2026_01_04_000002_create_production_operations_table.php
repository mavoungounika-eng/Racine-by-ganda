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
        Schema::create('production_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            
            $table->string('name')->comment('Operation name (e.g., Cutting, Assembly, Finishing)');
            $table->integer('sequence_order')->comment('Order in the production flow');
            $table->integer('standard_time_minutes')->nullable()->comment('Estimated time for this operation');
            $table->text('description')->nullable();
            
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['production_order_id', 'sequence_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_operations');
    }
};
