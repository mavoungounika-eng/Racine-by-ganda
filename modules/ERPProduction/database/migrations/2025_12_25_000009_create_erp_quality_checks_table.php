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
        Schema::create('erp_quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('erp_production_orders')->onDelete('restrict');
            $table->foreignId('work_step_id')->nullable()->constrained('erp_work_steps')->onDelete('set null');
            $table->enum('status', ['pass', 'rework', 'reject'])->default('pass');
            $table->decimal('quantity_checked', 10, 2);
            $table->decimal('quantity_passed', 10, 2)->default(0.00);
            $table->decimal('quantity_reworked', 10, 2)->default(0.00);
            $table->decimal('quantity_rejected', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->dateTime('checked_at');
            $table->foreignId('checked_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            // Index
            $table->index('production_order_id');
            $table->index('status');
            $table->index('checked_at');
        });
        
        // Ajouter contrainte CHECK (quantity_checked = passed + reworked + rejected)
        DB::statement('ALTER TABLE erp_quality_checks ADD CONSTRAINT chk_quality_quantities CHECK (quantity_checked = quantity_passed + quantity_reworked + quantity_rejected)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_quality_checks');
    }
};
