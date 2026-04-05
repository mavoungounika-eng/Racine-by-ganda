<?php

namespace App\Services\Financial;

use App\Exceptions\Accounting\AccountingNotBootstrappedException;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\ChartOfAccount;
use Illuminate\Support\Facades\Log;

/**
 * AccountingBootstrapService
 * 
 * RESPONSABILITÉ UNIQUE:
 * Vérifier que l'environnement comptable minimal est prêt.
 * 
 * RÈGLE ABSOLUE:
 * - Aucune création implicite
 * - Aucun fallback silencieux
 * - Fail fast, fail explicit
 */
final class AccountingBootstrapService
{
    /**
     * Vérifier que l'environnement est prêt pour settlement POS
     * 
     * @throws AccountingNotBootstrappedException Si bootstrap incomplet
     */
    public function assertReadyForPosSettlement(): void
    {
        // 1. Exercice fiscal actif
        $fiscalYear = FiscalYear::where('is_closed', false)
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$fiscalYear) {
            Log::channel('accounting')->error('POS settlement blocked: No active fiscal year', [
                'check' => 'fiscal_year',
            ]);
            throw new AccountingNotBootstrappedException('Active fiscal year');
        }

        // 2. Journal comptable requis (VTE)
        $journal = Journal::where('code', 'VTE')->first();

        if (!$journal) {
            Log::channel('accounting')->error('POS settlement blocked: Journal VTE not found', [
                'check' => 'journal',
                'required_code' => 'VTE',
            ]);
            throw new AccountingNotBootstrappedException('Journal VTE (Ventes)');
        }

        // 3. Comptes OHADA minimum
        $requiredAccounts = [
            '5700' => 'Caisse',
            '7011' => 'Ventes de marchandises',
            '4431' => 'TVA collectée',
        ];

        foreach ($requiredAccounts as $code => $label) {
            $account = ChartOfAccount::where('code', $code)->first();

            if (!$account) {
                Log::channel('accounting')->error('POS settlement blocked: Required account missing', [
                    'check' => 'chart_of_account',
                    'required_account' => $code,
                    'label' => $label,
                ]);
                throw new AccountingNotBootstrappedException("Account {$code} ({$label})");
            }
        }

        // ✅ Bootstrap OK
        Log::channel('accounting')->info('POS settlement bootstrap validated', [
            'fiscal_year_id' => $fiscalYear->id,
            'journal_id' => $journal->id,
            'accounts_validated' => array_keys($requiredAccounts),
        ]);
    }
}
