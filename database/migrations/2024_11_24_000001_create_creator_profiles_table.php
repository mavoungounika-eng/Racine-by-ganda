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
        Schema::create('creator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('brand_name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('photo')->nullable(); // Legacy
            $table->string('banner')->nullable(); // Legacy
            $table->string('location')->nullable(); // Ville / Pays
            $table->string('website')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('instagram')->nullable(); // Legacy
            $table->string('tiktok_url')->nullable();
            $table->string('facebook')->nullable(); // Legacy
            $table->string('type')->nullable(); // prêt-à-porter, sur mesure, accessoires...
            $table->string('legal_status')->nullable(); // particulier, auto-entrepreneur, SARL...
            $table->string('registration_number')->nullable(); // RCCM / NIU / autre
            $table->enum('payout_method', ['bank', 'mobile_money', 'other'])->nullable();
            $table->text('payout_details')->nullable(); // JSON ou texte
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('slug');
            $table->index('status');
            $table->index('is_active');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_profiles');
    }
};
