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
        Schema::create('accounting_bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('bank_account_code', 10)
                ->comment('Compte banque (5112, 5113, 5210, etc.)');
            $table->foreignId('entry_id')
                ->constrained('accounting_entries')
                ->onDelete('cascade')
                ->comment('Écriture comptable associée');
            $table->string('transaction_reference')
                ->comment('Référence transaction bancaire');
            $table->date('transaction_date')
                ->comment('Date transaction bancaire');
            $table->decimal('amount', 15, 2)
                ->comment('Montant transaction');
            $table->enum('status', ['pending', 'reconciled', 'rejected'])
                ->default('pending');
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index
            $table->index('bank_account_code');
            $table->index('entry_id');
            $table->index('transaction_reference');
            $table->index('status');
            
            // Foreign key
            $table->foreign('bank_account_code')
                ->references('code')
                ->on('accounting_chart_of_accounts')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_bank_reconciliations');
    }
};
