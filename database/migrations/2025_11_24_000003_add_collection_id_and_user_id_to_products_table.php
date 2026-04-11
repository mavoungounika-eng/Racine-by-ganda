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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('collection_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();
            
            $table->foreignId('user_id')
                ->nullable()
                ->after('collection_id')
                ->constrained()
                ->nullOnDelete()
                ->comment('Créateur du produit');
            
            // Index pour améliorer les performances
            $table->index('collection_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['collection_id', 'user_id']);
        });
    }
};
