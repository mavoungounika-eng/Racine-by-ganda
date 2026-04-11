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
        Schema::create('accounting_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')
                ->constrained('accounting_fiscal_years')
                ->onDelete('cascade');
            $table->string('account_code', 10);
            $table->date('period_start')->comment('Début période');
            $table->date('period_end')->comment('Fin période');
            
            // Soldes
            $table->decimal('opening_balance_debit', 15, 2)->default(0);
            $table->decimal('opening_balance_credit', 15, 2)->default(0);
            $table->decimal('period_debit', 15, 2)->default(0);
            $table->decimal('period_credit', 15, 2)->default(0);
            $table->decimal('closing_balance_debit', 15, 2)->default(0);
            $table->decimal('closing_balance_credit', 15, 2)->default(0);
            
            $table->timestamp('last_calculated_at');
            $table->timestamps();

            // Index
            $table->unique(['fiscal_year_id', 'account_code', 'period_start', 'period_end'], 
                'unique_balance');
            $table->index('fiscal_year_id');
            $table->index('account_code');
            $table->index(['period_start', 'period_end']);
            
            // Foreign key
            $table->foreign('account_code')
                ->references('code')
                ->on('accounting_chart_of_accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_balances');
    }
};
