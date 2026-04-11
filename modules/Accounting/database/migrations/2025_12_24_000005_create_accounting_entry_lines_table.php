<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')
                ->constrained('accounting_entries')
                ->onDelete('cascade');
            $table->string('account_code', 10)
                ->comment('Code compte');
            $table->integer('line_number')
                ->comment('Numéro ligne (ordre)');
            $table->text('description')->nullable()
                ->comment('Description ligne');
            
            // Montants
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            
            // TVA (si applicable)
            $table->decimal('amount_ht', 15, 2)->nullable()
                ->comment('Montant HT (si TVA)');
            $table->decimal('vat_amount', 15, 2)->nullable()
                ->comment('Montant TVA');
            $table->decimal('vat_rate', 5, 2)->nullable()
                ->comment('Taux TVA (%)');
            
            $table->timestamps();

            // Index
            $table->index('entry_id');
            $table->index('account_code');
            $table->index(['debit', 'credit']);
            
            // Foreign key
            $table->foreign('account_code')
                ->references('code')
                ->on('accounting_chart_of_accounts')
                ->onDelete('restrict');
        });
        
        // Contrainte: débit OU crédit (pas les deux)
        DB::statement('ALTER TABLE accounting_entry_lines ADD CONSTRAINT chk_debit_or_credit 
            CHECK ((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_entry_lines');
    }
};
