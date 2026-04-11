<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * V2.3 : Table pour les bundles (packs avec plan + add-ons)
     */
    public function up(): void
    {
        Schema::create('creator_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code unique du bundle (ex: starter_pack)');
            $table->string('name')->comment('Nom affichable du bundle');
            $table->text('description')->nullable()->comment('Description du bundle');
            $table->decimal('price', 10, 2)->comment('Prix du bundle');
            $table->foreignId('base_plan_id')
                ->constrained('creator_plans')
                ->comment('Plan de base inclus dans le bundle');
            $table->json('included_addon_ids')->nullable()->comment('IDs des add-ons inclus dans le bundle');
            $table->boolean('is_active')->default(true)->comment('Indique si le bundle est actif et disponible');
            $table->timestamps();
            
            $table->index('code');
            $table->index('base_plan_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_bundles');
    }
};
