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
        Schema::create('erp_wip_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('erp_production_orders')->onDelete('restrict');
            $table->foreignId('work_step_id')->nullable()->constrained('erp_work_steps')->onDelete('set null');
            $table->enum('type', [
                'production_started',
                'step_completed',
                'production_finished',
                'scrap',
                'rework'
            ]);
            $table->decimal('quantity', 10, 2);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index
            $table->index('production_order_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_wip_movements');
    }
};
