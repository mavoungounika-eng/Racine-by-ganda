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
        Schema::create('erp_quality_defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_check_id')->constrained('erp_quality_checks')->onDelete('cascade');
            $table->string('defect_code', 50);
            $table->enum('defect_category', ['material', 'process', 'human', 'machine']);
            $table->enum('severity', ['minor', 'major', 'critical']);
            $table->text('description');
            $table->timestamps();
            
            // Index
            $table->index('quality_check_id');
            $table->index('defect_category');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_quality_defects');
    }
};
