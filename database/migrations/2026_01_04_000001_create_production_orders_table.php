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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('of_number')->unique()->comment('Manufacturing Order Number (e.g., OF-26-001)');
            
            // Product Reference
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            
            // Workshop Assignment
            $table->foreignId('workshop_id')->nullable()->comment('Workshop or User responsible for production');
            
            // Quantities
            $table->integer('target_quantity')->comment('Planned production quantity');
            $table->integer('produced_qty_good')->default(0)->comment('First choice - sellable');
            $table->integer('produced_qty_second')->default(0)->comment('Second choice - outlet');
            $table->integer('rejected_qty')->default(0)->comment('Rejected - waste');
            
            // Status & Dates
            $table->enum('status', [
                'draft',
                'planned',
                'released',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('draft');
            
            $table->date('planned_start_date')->nullable();
            $table->date('deadline_date')->comment('Commitment date for stock availability');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // BOM Snapshot (frozen recipe)
            $table->json('bom_snapshot')->nullable()->comment('Bill of Materials version used');
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('deadline_date');
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
