<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_stocks', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable'); // Product or RawMaterial
            $table->enum('location', ['boutique', 'showroom', 'atelier', 'entrepot'])->default('entrepot');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('shelf_location')->nullable(); // Emplacement physique (ex: A-12-B)
            $table->timestamps();

            $table->unique(['stockable_type', 'stockable_id', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_stocks');
    }
};
