<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\AccountingEntryLine;
use Modules\Accounting\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportingService
{
    /**
     * Générer Balance Générale
     */
    public function generateTrialBalance(
        int $fiscalYearId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        
        if (!$startDate) $startDate = $fiscalYear->start_date;
        if (!$endDate) $endDate = $fiscalYear->end_date;
        
        // Récupérer tous les comptes avec mouvements
        $accounts = ChartOfAccount::active()
            ->whereHas('entryLines.entry', function ($q) use ($fiscalYearId, $startDate, $endDate) {
                $q->posted()
                  ->where('fiscal_year_id', $fiscalYearId)
                  ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->orderBy('code')
            ->get();
        
        $balances = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $totalBalanceDebit = 0;
        $totalBalanceCredit = 0;
        
        foreach ($accounts as $account) {
            $debit = AccountingEntryLine::where('account_code', $account->code)
                ->whereHas('entry', function ($q) use ($fiscalYearId, $startDate, $endDate) {
                    $q->posted()
                      ->where('fiscal_year_id', $fiscalYearId)
                      ->whereBetween('entry_date', [$startDate, $endDate]);
                })
                ->sum('debit');
            
            $credit = AccountingEntryLine::where('account_code', $account->code)
                ->whereHas('entry', function ($q) use ($fiscalYearId, $startDate, $endDate) {
                    $q->posted()
                      ->where('fiscal_year_id', $fiscalYearId)
                      ->whereBetween('entry_date', [$startDate, $endDate]);
                })
                ->sum('credit');
            
            $balance = $debit - $credit;
            
            $balances[] = [
                'code' => $account->code,
                'label' => $account->label,
                'account_type' => $account->account_type,
                'debit' => $debit,
                'credit' => $credit,
                'balance_debit' => $balance > 0 ? $balance : 0,
                'balance_credit' => $balance < 0 ? abs($balance) : 0,
            ];
            
            $totalDebit += $debit;
            $totalCredit += $credit;
            $totalBalanceDebit += $balance > 0 ? $balance : 0;
            $totalBalanceCredit += $balance < 0 ? abs($balance) : 0;
        }
        
        return [
            'fiscal_year' => $fiscalYear,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'balances' => $balances,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'total_balance_debit' => $totalBalanceDebit,
            'total_balance_credit' => $totalBalanceCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
    
    /**
     * Générer Grand Livre
     */
    public function generateGeneralLedger(
        string $accountCode,
        int $fiscalYearId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $account = ChartOfAccount::where('code', $accountCode)->firstOrFail();
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        
        if (!$startDate) $startDate = $fiscalYear->start_date;
        if (!$endDate) $endDate = $fiscalYear->end_date;
        
        $lines = AccountingEntryLine::where('account_code', $accountCode)
            ->whereHas('entry', function ($q) use ($fiscalYearId, $startDate, $endDate) {
                $q->posted()
                  ->where('fiscal_year_id', $fiscalYearId)
                  ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->with(['entry.journal'])
            ->get()
            ->sortBy('entry.entry_date');
        
        $movements = [];
        $balance = 0;
        
        foreach ($lines as $line) {
            $balance += $line->debit - $line->credit;
            
            $movements[] = [
                'date' => $line->entry->entry_date->format('Y-m-d'),
                'journal' => $line->entry->journal->code,
                'entry_number' => $line->entry->entry_number,
                'description' => $line->description ?? $line->entry->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'balance' => $balance,
            ];
        }
        
        return [
            'account' => $account,
            'fiscal_year' => $fiscalYear,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'movements' => $movements,
            'total_debit' => $lines->sum('debit'),
            'total_credit' => $lines->sum('credit'),
            'final_balance' => $balance,
        ];
    }
    
    /**
     * Générer Bilan OHADA
     */
    public function generateBalanceSheet(int $fiscalYearId, ?Carbon $date = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        
        if (!$date) $date = $fiscalYear->end_date;
        
        // ACTIF
        $actif = [
            'immobilisations' => $this->getAccountsBalance(['2'], $fiscalYearId, $date),
            'stocks' => $this->getAccountsBalance(['3'], $fiscalYearId, $date),
            'creances_clients' => $this->getAccountsBalance(['411'], $fiscalYearId, $date),
            'tresorerie_banques' => $this->getAccountsBalance(['521', '5210', '5211', '5212'], $fiscalYearId, $date),
            'tresorerie_caisse' => $this->getAccountsBalance(['57', '5700'], $fiscalYearId, $date),
        ];
        
        // PASSIF
        $passif = [
            'capitaux_propres' => $this->getAccountsBalance(['1'], $fiscalYearId, $date),
            'dettes_fournisseurs' => abs($this->getAccountsBalance(['401', '4011', '4012', '4013'], $fiscalYearId, $date)),
            'dettes_fiscales' => abs($this->getAccountsBalance(['44', '4421', '4422'], $fiscalYearId, $date)),
            'dettes_createurs' => abs($this->getAccountsBalance(['4671'], $fiscalYearId, $date)),
        ];
        
        // Résultat
        $resultat = $this->calculateNetIncome($fiscalYearId, $fiscalYear->start_date, $date);
        
        $totalActif = array_sum($actif);
        $totalPassif = array_sum($passif) + $resultat;
        
        return [
            'fiscal_year' => $fiscalYear,
            'date' => $date,
            'actif' => $actif,
            'passif' => $passif,
            'resultat' => $resultat,
            'total_actif' => $totalActif,
            'total_passif' => $totalPassif,
            'is_balanced' => abs($totalActif - $totalPassif) < 0.01,
        ];
    }
    
    /**
     * Générer Compte de Résultat OHADA
     */
    public function generateIncomeStatement(
        int $fiscalYearId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        
        if (!$startDate) $startDate = $fiscalYear->start_date;
        if (!$endDate) $endDate = $fiscalYear->end_date;
        
        // CHARGES (Classe 6) - Valeur absolue car débit
        $charges = [
            'achats_marchandises' => abs($this->getAccountsBalance(['601', '6011', '6012'], $fiscalYearId, $endDate, $startDate)),
            'transports' => abs($this->getAccountsBalance(['611', '6111'], $fiscalYearId, $endDate, $startDate)),
            'frais_bancaires' => abs($this->getAccountsBalance(['624', '6241', '6242'], $fiscalYearId, $endDate, $startDate)),
            'salaires' => abs($this->getAccountsBalance(['661', '6611', '6612'], $fiscalYearId, $endDate, $startDate)),
        ];
        
        // PRODUITS (Classe 7) - Valeur absolue car crédit
        $produits = [
            'ventes_boutique' => abs($this->getAccountsBalance(['7011'], $fiscalYearId, $endDate, $startDate)),
            'ventes_marketplace' => abs($this->getAccountsBalance(['7012'], $fiscalYearId, $endDate, $startDate)),
            'commissions' => abs($this->getAccountsBalance(['7013'], $fiscalYearId, $endDate, $startDate)),
        ];
        
        $totalCharges = array_sum($charges);
        $totalProduits = array_sum($produits);
        $resultat = $totalProduits - $totalCharges;
        
        return [
            'fiscal_year' => $fiscalYear,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'charges' => $charges,
            'produits' => $produits,
            'total_charges' => $totalCharges,
            'total_produits' => $totalProduits,
            'resultat' => $resultat,
            'type' => $resultat >= 0 ? 'benefice' : 'perte',
        ];
    }
    
    /**
     * Calculer résultat net (Produits - Charges)
     */
    protected function calculateNetIncome(
        int $fiscalYearId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $produits = abs($this->getAccountsBalance(['7'], $fiscalYearId, $endDate, $startDate));
        $charges = abs($this->getAccountsBalance(['6'], $fiscalYearId, $endDate, $startDate));
        
        return $produits - $charges;
    }
    
    /**
     * Calculer solde comptes (helper)
     */
    protected function getAccountsBalance(
        array $accountPrefixes,
        int $fiscalYearId,
        Carbon $endDate,
        ?Carbon $startDate = null
    ): float {
        $accountCodes = ChartOfAccount::where(function ($q) use ($accountPrefixes) {
            foreach ($accountPrefixes as $prefix) {
                $q->orWhere('code', 'like', $prefix . '%');
            }
        })->pluck('code');
        
        $query = AccountingEntryLine::whereIn('account_code', $accountCodes)
            ->whereHas('entry', function ($q) use ($fiscalYearId, $endDate, $startDate) {
                $q->posted()
                  ->where('fiscal_year_id', $fiscalYearId)
                  ->where('entry_date', '<=', $endDate);
                
                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }
            });
        
        $debit = (clone $query)->sum('debit');
        $credit = (clone $query)->sum('credit');
        
        return $debit - $credit;
    }
}
