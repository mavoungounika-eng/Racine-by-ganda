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
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            
            // Message
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            
            // Fichier
            $table->string('file_path');
            $table->string('file_name');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size'); // en bytes
            $table->string('mime_type');
            
            // Type de fichier
            $table->string('file_type')->default('file'); // image, document, file
            
            // Dimensions pour les images
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            
            // Thumbnail pour les images
            $table->string('thumbnail_path')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('message_id');
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
