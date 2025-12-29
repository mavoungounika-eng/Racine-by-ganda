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
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique()
                ->comment('Numéro écriture (ex: VTE-2025-001)');
            $table->foreignId('journal_id')
                ->constrained('accounting_journals')
                ->onDelete('restrict');
            $table->foreignId('fiscal_year_id')
                ->constrained('accounting_fiscal_years')
                ->onDelete('restrict');
            $table->date('entry_date')->comment('Date écriture');
            $table->text('description')->comment('Description écriture');
            $table->string('reference', 100)->nullable()
                ->comment('Référence externe');
            $table->string('reference_type', 50)->nullable()
                ->comment('Type référence (order, payment, purchase)');
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID référence');
            
            // Montants
            $table->decimal('total_debit', 15, 2)->default(0)
                ->comment('Total débit');
            $table->decimal('total_credit', 15, 2)->default(0)
                ->comment('Total crédit');
            
            // Statut
            $table->boolean('is_posted')->default(false)
                ->comment('Écriture validée (irréversible)');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Audit
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete pour audit (uniquement si non posted)');

            // Index
            $table->index('entry_number');
            $table->index('journal_id');
            $table->index('fiscal_year_id');
            $table->index('entry_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index('is_posted');
        });
        
        // Contrainte: débit = crédit si posted
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE accounting_entries ADD CONSTRAINT chk_balanced 
                CHECK (is_posted = 0 OR total_debit = total_credit)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
