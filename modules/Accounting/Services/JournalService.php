<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Support\Collection;

class JournalService
{
    /**
     * Obtenir un journal par son code
     */
    public function getJournal(string $code): ?Journal
    {
        return Journal::where('code', $code)->first();
    }

    /**
     * Obtenir tous les journaux actifs
     */
    public function getActiveJournals(): Collection
    {
        return Journal::active()->orderBy('code')->get();
    }

    /**
     * Obtenir journaux par type
     */
    public function getJournalsByType(string $type): Collection
    {
        return Journal::active()->ofType($type)->orderBy('code')->get();
    }

    /**
     * Créer un nouveau journal
     */
    public function createJournal(array $data): Journal
    {
        // Vérifier code unique
        if (Journal::where('code', $data['code'])->exists()) {
            throw new LedgerException("Le journal {$data['code']} existe déjà");
        }

        // Valider code (3-10 caractères alphanumériques majuscules)
        if (!$this->validateJournalCode($data['code'])) {
            throw new LedgerException("Le code journal {$data['code']} est invalide (3-10 caractères majuscules)");
        }

        return Journal::create($data);
    }

    /**
     * Mettre à jour un journal
     */
    public function updateJournal(string $code, array $data): Journal
    {
        $journal = Journal::where('code', $code)->firstOrFail();

        // Empêcher modification code si journal a des écritures
        if (isset($data['code']) && $journal->entries()->exists()) {
            throw new LedgerException("Le code du journal {$code} ne peut pas être modifié car il a des écritures");
        }

        $journal->update($data);

        return $journal->fresh();
    }

    /**
     * Désactiver un journal
     */
    public function deactivateJournal(string $code): Journal
    {
        $journal = Journal::where('code', $code)->firstOrFail();

        // Vérifier pas d'écritures brouillon
        if ($journal->entries()->draft()->exists()) {
            throw new LedgerException("Le journal {$code} a des écritures brouillon");
        }

        $journal->update(['is_active' => false]);

        return $journal->fresh();
    }

    /**
     * Activer un journal
     */
    public function activateJournal(string $code): Journal
    {
        $journal = Journal::where('code', $code)->firstOrFail();
        $journal->update(['is_active' => true]);

        return $journal->fresh();
    }

    /**
     * Valider code journal
     */
    public function validateJournalCode(string $code): bool
    {
        // Code journal: 3-10 caractères alphanumériques majuscules
        return preg_match('/^[A-Z0-9]{3,10}$/', $code) === 1;
    }

    /**
     * Obtenir statistiques journal
     */
    public function getJournalStats(string $code): array
    {
        $journal = Journal::where('code', $code)->firstOrFail();

        return [
            'code' => $journal->code,
            'name' => $journal->name,
            'type' => $journal->type,
            'is_active' => $journal->is_active,
            'total_entries' => $journal->entries()->count(),
            'posted_entries' => $journal->entries()->posted()->count(),
            'draft_entries' => $journal->entries()->draft()->count(),
            'total_debit' => $journal->entries()->posted()->sum('total_debit'),
            'total_credit' => $journal->entries()->posted()->sum('total_credit'),
        ];
    }

    /**
     * Obtenir dernier numéro d'écriture
     */
    public function getLastEntryNumber(string $code, int $year): ?string
    {
        $journal = Journal::where('code', $code)->firstOrFail();

        return $journal->entries()
            ->whereYear('entry_date', $year)
            ->orderBy('entry_number', 'desc')
            ->value('entry_number');
    }
}
