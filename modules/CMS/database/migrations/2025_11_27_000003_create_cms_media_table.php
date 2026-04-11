<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // en bytes
            $table->string('disk')->default('public');
            
            // Dimensions pour les images
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            
            // Organisation
            $table->string('folder')->default('uploads');
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('tags')->nullable();
            
            // Tracking
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media');
    }
};

