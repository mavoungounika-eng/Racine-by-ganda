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
        Schema::create('production_quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            
            // Inspector
            $table->foreignId('inspector_id')->constrained('users')->restrictOnDelete();
            
            // Quality Results
            $table->integer('inspected_qty')->comment('Total quantity inspected');
            $table->integer('passed_qty')->comment('Quantity passed inspection');
            $table->integer('failed_qty')->comment('Quantity failed inspection');
            
            // Defect Classification
            $table->string('defect_type')->nullable()->comment('Primary defect type');
            $table->json('defect_details')->nullable()->comment('Detailed defect breakdown');
            
            // Severity
            $table->enum('severity', ['minor', 'major', 'critical'])->nullable();
            
            // Decision
            $table->enum('decision', ['approved', 'rework', 'downgrade', 'reject'])->default('approved');
            
            $table->text('comments')->nullable();
            $table->timestamp('inspected_at')->useCurrent();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['production_order_id', 'decision']);
            $table->index('inspected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_quality_controls');
    }
};
