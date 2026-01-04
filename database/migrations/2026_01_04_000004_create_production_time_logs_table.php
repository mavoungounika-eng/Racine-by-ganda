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
        Schema::create('production_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_operation_id')->constrained('production_operations')->cascadeOnDelete();
            
            // Operator
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            
            // Time tracking
            $table->integer('duration_minutes')->comment('Actual time spent on this operation');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            
            // Workstation (optional - for machine tracking)
            $table->string('workstation_id')->nullable()->comment('Machine or station identifier');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['production_operation_id', 'operator_id']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_time_logs');
    }
};
