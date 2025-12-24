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
        Schema::create('accounting_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nom exercice (ex: Exercice 2025)');
            $table->date('start_date')->comment('Date début exercice');
            $table->date('end_date')->comment('Date fin exercice');
            $table->boolean('is_closed')->default(false)->comment('Exercice clôturé');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('is_closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_fiscal_years');
    }
};
