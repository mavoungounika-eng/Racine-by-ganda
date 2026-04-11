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
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->decimal('quality_score', 5, 2)->nullable()->after('is_active'); // Score de qualité (0-100)
            $table->decimal('completeness_score', 5, 2)->nullable()->after('quality_score'); // Score de complétude (0-100)
            $table->decimal('performance_score', 5, 2)->nullable()->after('completeness_score'); // Score de performance (0-100)
            $table->decimal('overall_score', 5, 2)->nullable()->after('performance_score'); // Score global (0-100)
            $table->timestamp('last_score_calculated_at')->nullable()->after('overall_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'quality_score',
                'completeness_score',
                'performance_score',
                'overall_score',
                'last_score_calculated_at',
            ]);
        });
    }
};

