<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FiscalYearService
{
    /**
     * Obtenir exercice en cours
     */
    public function getCurrentFiscalYear(): ?FiscalYear
    {
        return FiscalYear::current()->first();
    }

    /**
     * Obtenir tous les exercices
     */
    public function getAllFiscalYears(): Collection
    {
        return FiscalYear::orderBy('start_date', 'desc')->get();
    }

    /**
     * Obtenir exercices ouverts
     */
    public function getOpenFiscalYears(): Collection
    {
        return FiscalYear::open()->orderBy('start_date', 'desc')->get();
    }

    /**
     * Créer un nouvel exercice
     */
    public function createFiscalYear(array $data): FiscalYear
    {
        // Vérifier pas de chevauchement
        $overlapping = FiscalYear::where(function ($q) use ($data) {
            $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
              ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
              ->orWhere(function ($q2) use ($data) {
                  $q2->where('start_date', '<=', $data['start_date'])
                     ->where('end_date', '>=', $data['end_date']);
              });
        })->exists();

        if ($overlapping) {
            throw new LedgerException("L'exercice chevauche un exercice existant");
        }

        // Vérifier durée (généralement 12 mois)
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $months = $start->diffInMonths($end);

        if ($months < 11 || $months > 13) {
            throw new LedgerException("L'exercice doit durer environ 12 mois (durée: {$months} mois)");
        }

        return FiscalYear::create($data);
    }

    /**
     * Clôturer un exercice
     */
    public function closeFiscalYear(int $fiscalYearId): FiscalYear
    {
        return DB::transaction(function () use ($fiscalYearId) {
            $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

            // Vérifier exercice pas déjà clôturé
            if ($fiscalYear->is_closed) {
                throw new LedgerException("L'exercice {$fiscalYear->name} est déjà clôturé");
            }

            // Vérifier toutes les écritures sont postées
            $draftEntries = AccountingEntry::where('fiscal_year_id', $fiscalYear->id)
                ->draft()
                ->count();

            if ($draftEntries > 0) {
                throw new LedgerException("L'exercice {$fiscalYear->name} a {$draftEntries} écriture(s) brouillon");
            }

            // Vérifier toutes les écritures sont équilibrées
            $unbalancedEntries = AccountingEntry::where('fiscal_year_id', $fiscalYear->id)
                ->whereRaw('total_debit != total_credit')
                ->count();

            if ($unbalancedEntries > 0) {
                throw new LedgerException("L'exercice {$fiscalYear->name} a {$unbalancedEntries} écriture(s) non équilibrée(s)");
            }

            // Clôturer
            $fiscalYear->update([
                'is_closed' => true,
                'closed_at' => now(),
                'closed_by' => Auth::id() ?? 1,
            ]);

            return $fiscalYear->fresh();
        });
    }

    /**
     * Rouvrir un exercice (avec précaution)
     */
    public function reopenFiscalYear(int $fiscalYearId): FiscalYear
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        if (!$fiscalYear->is_closed) {
            throw new LedgerException("L'exercice {$fiscalYear->name} n'est pas clôturé");
        }

        // Vérifier pas d'exercice postérieur clôturé
        $laterClosedExists = FiscalYear::where('start_date', '>', $fiscalYear->start_date)
            ->where('is_closed', true)
            ->exists();

        if ($laterClosedExists) {
            throw new LedgerException("Impossible de rouvrir {$fiscalYear->name} : un exercice postérieur est clôturé");
        }

        $fiscalYear->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $fiscalYear->fresh();
    }

    /**
     * Obtenir statistiques exercice
     */
    public function getFiscalYearStats(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $entries = AccountingEntry::where('fiscal_year_id', $fiscalYear->id);

        return [
            'name' => $fiscalYear->name,
            'start_date' => $fiscalYear->start_date->format('Y-m-d'),
            'end_date' => $fiscalYear->end_date->format('Y-m-d'),
            'is_closed' => $fiscalYear->is_closed,
            'total_entries' => $entries->count(),
            'posted_entries' => $entries->posted()->count(),
            'draft_entries' => $entries->draft()->count(),
            'total_debit' => $entries->posted()->sum('total_debit'),
            'total_credit' => $entries->posted()->sum('total_credit'),
            'entries_by_journal' => $entries->posted()
                ->select('journal_id', DB::raw('count(*) as count'))
                ->groupBy('journal_id')
                ->with('journal:id,code,name')
                ->get()
                ->map(fn($e) => [
                    'journal' => $e->journal->code,
                    'count' => $e->count,
                ]),
        ];
    }

    /**
     * Vérifier si date appartient à un exercice ouvert
     */
    public function isDateInOpenFiscalYear(string $date): bool
    {
        return FiscalYear::open()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Obtenir exercice pour une date
     */
    public function getFiscalYearForDate(string $date): ?FiscalYear
    {
        return FiscalYear::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
}
