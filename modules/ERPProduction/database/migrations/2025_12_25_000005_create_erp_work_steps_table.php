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
        Schema::create('erp_work_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('erp_production_orders')->onDelete('cascade');
            $table->foreignId('work_center_id')->nullable()->constrained('erp_work_centers')->onDelete('restrict');
            $table->integer('sequence')->default(0); // Ordre d'exécution
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->decimal('estimated_duration', 8, 2)->nullable(); // Durée estimée (heures)
            $table->decimal('actual_duration', 8, 2)->nullable(); // Durée réelle (heures)
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('production_order_id');
            $table->index('status');
            $table->index('sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_work_steps');
    }
};
