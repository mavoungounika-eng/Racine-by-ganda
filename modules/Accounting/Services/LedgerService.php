<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\AccountingEntryLine;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LedgerService
{
    /**
     * Créer une écriture comptable
     */
    public function createEntry(array $data): AccountingEntry
    {
        return DB::transaction(function () use ($data) {
            $journal = Journal::findOrFail($data['journal_id']);
            $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);
            
            if ($fiscalYear->is_closed) {
                throw new LedgerException("Exercice {$fiscalYear->name} est clôturé");
            }
            
            $entryNumber = $this->generateEntryNumber($journal, $data['entry_date']);
            
            return AccountingEntry::create([
                'entry_number' => $entryNumber,
                'journal_id' => $data['journal_id'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => Auth::id() ?? 1,
            ]);
        });
    }

    /**
     * Ajouter une ligne d'écriture
     */
    public function addLine(
        AccountingEntry $entry,
        string $accountCode,
        float $debit,
        float $credit,
        ?string $description = null,
        ?array $vatData = null
    ): AccountingEntryLine {
        if ($entry->is_posted) {
            throw new LedgerException("Écriture {$entry->entry_number} est postée (irréversible)");
        }
        
        ChartOfAccount::where('code', $accountCode)->firstOrFail();
        
        if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
            throw new LedgerException("Une ligne doit avoir débit OU crédit");
        }
        
        $lineNumber = $entry->lines()->max('line_number') + 1;
        
        $lineData = [
            'entry_id' => $entry->id,
            'account_code' => $accountCode,
            'line_number' => $lineNumber,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
        ];
        
        if ($vatData) {
            $lineData['amount_ht'] = $vatData['amount_ht'];
            $lineData['vat_amount'] = $vatData['vat_amount'];
            $lineData['vat_rate'] = $vatData['vat_rate'];
        }
        
        $line = AccountingEntryLine::create($lineData);
        $this->recalculateTotals($entry);
        
        return $line;
    }

    /**
     * Valider équilibre écriture
     */
    public function validateBalance(AccountingEntry $entry): bool
    {
        $entry->refresh();
        return abs($entry->total_debit - $entry->total_credit) < 0.01;
    }

    /**
     * Poster écriture (irréversible)
     */
    public function postEntry(AccountingEntry $entry): void
    {
        if ($entry->is_posted) {
            throw new LedgerException("Écriture {$entry->entry_number} déjà postée");
        }
        
        if (!$this->validateBalance($entry)) {
            throw new LedgerException("Écriture {$entry->entry_number} non équilibrée");
        }
        
        DB::transaction(function () use ($entry) {
            $entry->update([
                'is_posted' => true,
                'posted_at' => now(),
                'posted_by' => Auth::id() ?? 1,
            ]);
        });
    }

    /**
     * Créer écriture vente simple (avec TVA)
     */
    public function createSaleEntry(
        $order,
        string $journalCode,
        string $debitAccount,
        string $creditAccount,
        float $totalTTC,
        float $vatRate = 18.0
    ): AccountingEntry {
        return DB::transaction(function () use ($order, $journalCode, $debitAccount, $creditAccount, $totalTTC, $vatRate) {
            $journal = Journal::where('code', $journalCode)->firstOrFail();
            $fiscalYear = $this->getCurrentFiscalYear();
            
            $amountHT = $totalTTC / (1 + $vatRate / 100);
            $vatAmount = $totalTTC - $amountHT;
            
            $entry = $this->createEntry([
                'journal_id' => $journal->id,
                'fiscal_year_id' => $fiscalYear->id,
                'entry_date' => now()->toDateString(),
                'description' => "Vente commande #{$order->id}",
                'reference_type' => 'order',
                'reference_id' => $order->id,
            ]);
            
            $this->addLine($entry, $debitAccount, $totalTTC, 0, "Encaissement commande #{$order->id}");
            $this->addLine($entry, $creditAccount, 0, $amountHT, "Vente HT", [
                'amount_ht' => $amountHT,
                'vat_amount' => $vatAmount,
                'vat_rate' => $vatRate,
            ]);
            $this->addLine($entry, '4421', 0, $vatAmount, "TVA collectée {$vatRate}%");
            
            $this->postEntry($entry);
            
            return $entry;
        });
    }

    /**
     * Créer écriture vente marketplace (avec commission et TVA)
     */
    public function createMarketplaceSaleEntry(
        $order,
        string $journalCode,
        string $debitAccount,
        float $totalTTC,
        float $commissionRate = 0.15,
        float $vatRate = 18.0
    ): AccountingEntry {
        return DB::transaction(function () use ($order, $journalCode, $debitAccount, $totalTTC, $commissionRate, $vatRate) {
            $journal = Journal::where('code', $journalCode)->firstOrFail();
            $fiscalYear = $this->getCurrentFiscalYear();
            
            $amountHT = $totalTTC / (1 + $vatRate / 100);
            $vatAmount = $totalTTC - $amountHT;
            $commissionHT = $amountHT * $commissionRate;
            $creatorAmountHT = $amountHT - $commissionHT;
            
            $entry = $this->createEntry([
                'journal_id' => $journal->id,
                'fiscal_year_id' => $fiscalYear->id,
                'entry_date' => now()->toDateString(),
                'description' => "Vente marketplace commande #{$order->id}",
                'reference_type' => 'order',
                'reference_id' => $order->id,
            ]);
            
            $this->addLine($entry, $debitAccount, $totalTTC, 0, "Encaissement marketplace");
            $this->addLine($entry, '4671', 0, $creatorAmountHT, "Dette créateur");
            $this->addLine($entry, '7013', 0, $commissionHT, "Commission marketplace");
            $this->addLine($entry, '4421', 0, $vatAmount, "TVA collectée {$vatRate}%");
            
            $this->postEntry($entry);
            
            return $entry;
        });
    }

    /**
     * Contre-passation (annulation écriture)
     */
    public function reverseEntry(AccountingEntry $originalEntry, string $reason): AccountingEntry
    {
        if (!$originalEntry->is_posted) {
            throw new LedgerException("Seules les écritures postées peuvent être contre-passées");
        }
        
        return DB::transaction(function () use ($originalEntry, $reason) {
            $fiscalYear = $this->getCurrentFiscalYear();
            
            $reversalEntry = $this->createEntry([
                'journal_id' => $originalEntry->journal_id,
                'fiscal_year_id' => $fiscalYear->id,
                'entry_date' => now()->toDateString(),
                'description' => "CONTRE-PASSATION: {$originalEntry->description} | Raison: {$reason}",
                'reference_type' => $originalEntry->reference_type,
                'reference_id' => $originalEntry->reference_id,
            ]);
            
            foreach ($originalEntry->lines as $line) {
                $this->addLine(
                    $reversalEntry,
                    $line->account_code,
                    $line->credit,
                    $line->debit,
                    "Contre-passation ligne #{$line->line_number}"
                );
            }
            
            $this->postEntry($reversalEntry);
            
            return $reversalEntry;
        });
    }

    // --- Méthodes privées ---

    private function generateEntryNumber(Journal $journal, string $date): string
    {
        $year = Carbon::parse($date)->year;
        $lastEntry = AccountingEntry::where('journal_id', $journal->id)
            ->whereYear('entry_date', $year)
            ->orderBy('entry_number', 'desc')
            ->first();
        
        $sequence = $lastEntry ? (int) substr($lastEntry->entry_number, -3) + 1 : 1;
        
        return sprintf('%s-%d-%03d', $journal->code, $year, $sequence);
    }

    private function recalculateTotals(AccountingEntry $entry): void
    {
        $totals = $entry->lines()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();
        
        $entry->update([
            'total_debit' => $totals->total_debit ?? 0,
            'total_credit' => $totals->total_credit ?? 0,
        ]);
    }

    private function getCurrentFiscalYear(): FiscalYear
    {
        return FiscalYear::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->firstOrFail();
    }
}
