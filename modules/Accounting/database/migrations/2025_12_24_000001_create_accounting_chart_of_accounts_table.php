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
        Schema::create('accounting_chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Code comptable OHADA');
            $table->string('label')->comment('Libellé du compte');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense'])
                ->comment('Type de compte');
            $table->string('parent_code', 10)->nullable()
                ->comment('Code parent pour hiérarchie');
            $table->enum('normal_balance', ['debit', 'credit'])
                ->comment('Sens normal du compte');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)
                ->comment('Compte système non modifiable');
            $table->boolean('requires_vat')->default(false)
                ->comment('Compte soumis à TVA');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('account_type');
            $table->index('parent_code');
        });

        // Ajouter foreign key après création table (évite problème auto-référence)
        Schema::table('accounting_chart_of_accounts', function (Blueprint $table) {
            $table->foreign('parent_code')
                ->references('code')
                ->on('accounting_chart_of_accounts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_chart_of_accounts');
    }
};
