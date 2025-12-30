<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\AccountingEntryLine;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BankReconciliationService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Rapprocher encaissements Stripe
     */
    public function reconcileStripePayout(string $payoutId, float $amount, Carbon $arrivalDate): BankReconciliation
    {
        return DB::transaction(function () use ($payoutId, $amount, $arrivalDate) {
            // Vérifier pas déjà rapproché
            if (BankReconciliation::where('transaction_reference', $payoutId)->exists()) {
                throw new LedgerException("Payout Stripe {$payoutId} déjà rapproché");
            }

            // Vérifier montant en attente suffisant
            $pendingAmount = $this->getPendingStripeAmount();
            if ($amount > $pendingAmount) {
                throw new LedgerException("Montant payout ({$amount}) supérieur aux encaissements en attente ({$pendingAmount})");
            }

            // Créer écriture rapprochement
            $entry = $this->createReconciliationEntry(
                fromAccount: '5112', // Encaissements Stripe (attente)
                toAccount: '5211',   // Banque Stripe
                amount: $amount,
                reference: $payoutId,
                date: $arrivalDate,
                description: "Rapprochement Stripe Payout {$payoutId}"
            );

            // Créer enregistrement rapprochement
            $reconciliation = BankReconciliation::create([
                'bank_account_code' => '5211',
                'entry_id' => $entry->id,
                'transaction_reference' => $payoutId,
                'transaction_date' => $arrivalDate,
                'amount' => $amount,
                'status' => 'reconciled',
                'reconciled_at' => now(),
                'reconciled_by' => Auth::id() ?? 1,
                'notes' => "Rapprochement automatique Stripe",
            ]);

            // Mettre à jour référence dans l'écriture
            $entry->update(['reference_id' => $reconciliation->id]);

            return $reconciliation;
        });
    }

    /**
     * Rapprocher encaissements Monetbil
     */
    public function reconcileMonetbilPayout(string $payoutId, float $amount, Carbon $arrivalDate): BankReconciliation
    {
        return DB::transaction(function () use ($payoutId, $amount, $arrivalDate) {
            // Vérifier pas déjà rapproché
            if (BankReconciliation::where('transaction_reference', $payoutId)->exists()) {
                throw new LedgerException("Payout Monetbil {$payoutId} déjà rapproché");
            }

            // Vérifier montant en attente suffisant
            $pendingAmount = $this->getPendingMonetbilAmount();
            if ($amount > $pendingAmount) {
                throw new LedgerException("Montant payout ({$amount}) supérieur aux encaissements en attente ({$pendingAmount})");
            }

            // Créer écriture rapprochement
            $entry = $this->createReconciliationEntry(
                fromAccount: '5113', // Encaissements Monetbil (attente)
                toAccount: '5212',   // Banque Monetbil
                amount: $amount,
                reference: $payoutId,
                date: $arrivalDate,
                description: "Rapprochement Monetbil Payout {$payoutId}"
            );

            // Créer enregistrement rapprochement
            $reconciliation = BankReconciliation::create([
                'bank_account_code' => '5212',
                'entry_id' => $entry->id,
                'transaction_reference' => $payoutId,
                'transaction_date' => $arrivalDate,
                'amount' => $amount,
                'status' => 'reconciled',
                'reconciled_at' => now(),
                'reconciled_by' => Auth::id() ?? 1,
                'notes' => "Rapprochement automatique Monetbil",
            ]);

            // Mettre à jour référence dans l'écriture
            $entry->update(['reference_id' => $reconciliation->id]);

            return $reconciliation;
        });
    }

    /**
     * Créer écriture de rapprochement
     */
    protected function createReconciliationEntry(
        string $fromAccount,
        string $toAccount,
        float $amount,
        string $reference,
        Carbon $date,
        string $description
    ): AccountingEntry {
        $journal = Journal::where('code', 'BNQ')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => $date->toDateString(),
            'description' => $description,
            'reference_type' => 'bank_reconciliation',
            'reference' => $reference,
        ]);

        // Débit banque réelle
        $this->ledgerService->addLine(
            $entry,
            $toAccount,
            $amount,
            0,
            "Virement bancaire reçu"
        );

        // Crédit compte attente
        $this->ledgerService->addLine(
            $entry,
            $fromAccount,
            0,
            $amount,
            "Rapprochement encaissements"
        );

        // Poster automatiquement
        $this->ledgerService->postEntry($entry);

        return $entry;
    }

    /**
     * Obtenir montant en attente Stripe (solde compte 5112)
     */
    public function getPendingStripeAmount(): float
    {
        $debit = AccountingEntryLine::where('account_code', '5112')
            ->whereHas('entry', fn($q) => $q->posted())
            ->sum('debit');

        $credit = AccountingEntryLine::where('account_code', '5112')
            ->whereHas('entry', fn($q) => $q->posted())
            ->sum('credit');

        return $debit - $credit;
    }

    /**
     * Obtenir montant en attente Monetbil (solde compte 5113)
     */
    public function getPendingMonetbilAmount(): float
    {
        $debit = AccountingEntryLine::where('account_code', '5113')
            ->whereHas('entry', fn($q) => $q->posted())
            ->sum('debit');

        $credit = AccountingEntryLine::where('account_code', '5113')
            ->whereHas('entry', fn($q) => $q->posted())
            ->sum('credit');

        return $debit - $credit;
    }

    /**
     * Obtenir rapprochements en attente
     */
    public function getPendingReconciliations(): \Illuminate\Support\Collection
    {
        return BankReconciliation::where('status', 'pending')
            ->with(['entry', 'bankAccount'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Obtenir rapprochements validés
     */
    public function getReconciledReconciliations(?Carbon $startDate = null, ?Carbon $endDate = null): \Illuminate\Support\Collection
    {
        $query = BankReconciliation::where('status', 'reconciled')
            ->with(['entry', 'bankAccount', 'reconciledByUser']);

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->orderBy('transaction_date', 'desc')->get();
    }

    /**
     * Rejeter un rapprochement
     */
    public function rejectReconciliation(int $reconciliationId, string $reason): BankReconciliation
    {
        $reconciliation = BankReconciliation::findOrFail($reconciliationId);

        if ($reconciliation->status === 'reconciled') {
            throw new LedgerException("Rapprochement déjà validé, impossible de rejeter");
        }

        $reconciliation->update([
            'status' => 'rejected',
            'notes' => "Rejeté: {$reason}",
        ]);

        return $reconciliation->fresh();
    }
}
