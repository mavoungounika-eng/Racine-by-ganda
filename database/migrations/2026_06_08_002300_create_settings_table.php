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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255)->unique()->index();
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // string|integer|float|boolean|json
            $table->string('group', 100)->index(); // general|marketplace|payments|email|security|appearance|advanced|profile
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false); // Pour API keys et secrets
            $table->boolean('is_public')->default(false); // Si accessible côté frontend
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index pour performance
            $table->index(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
