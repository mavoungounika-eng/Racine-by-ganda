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
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Code journal (VTE, ACH, BNQ, etc.)');
            $table->string('name', 100)->comment('Nom du journal');
            $table->enum('type', ['sales', 'purchases', 'bank', 'cash', 'general'])
                ->comment('Type de journal');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_journals');
    }
};
