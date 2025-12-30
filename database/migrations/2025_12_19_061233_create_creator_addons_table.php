<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * V2.2 : Table pour les add-ons (features vendues à l'unité)
     */
    public function up(): void
    {
        Schema::create('creator_addons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code unique de l\'add-on (ex: api_access)');
            $table->string('name')->comment('Nom affichable de l\'add-on');
            $table->text('description')->nullable()->comment('Description de l\'add-on');
            $table->decimal('price', 10, 2)->comment('Prix de l\'add-on');
            $table->string('capability_key')->comment('Capability activée par cet add-on (ex: can_use_api)');
            $table->json('capability_value')->nullable()->comment('Valeur de la capability (si nécessaire)');
            $table->enum('billing_cycle', ['one_time', 'monthly', 'annually'])->default('one_time')->comment('Cycle de facturation');
            $table->boolean('is_active')->default(true)->comment('Indique si l\'add-on est actif et disponible');
            $table->timestamps();
            
            $table->index('code');
            $table->index('capability_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_addons');
    }
};
