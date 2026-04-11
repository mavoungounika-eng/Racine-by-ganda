<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class AccountingEntry extends Model
{
    use SoftDeletes;

    protected $table = 'accounting_entries';

    protected $fillable = [
        'entry_number',
        'journal_id',
        'fiscal_year_id',
        'entry_date',
        'description',
        'reference',
        'reference_type',
        'reference_id',
        'total_debit',
        'total_credit',
        'is_posted',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    /**
     * Relation: Journal
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    /**
     * Relation: Exercice comptable
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    /**
     * Relation: Lignes d'écriture
     */
    public function lines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class, 'entry_id')->orderBy('line_number');
    }

    /**
     * Relation: Utilisateur créateur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation: Utilisateur ayant posté
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Relation: Rapprochement bancaire
     */
    public function bankReconciliation(): HasMany
    {
        return $this->hasMany(BankReconciliation::class, 'entry_id');
    }

    /**
     * Scope: Écritures postées
     */
    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }

    /**
     * Scope: Brouillons
     */
    public function scopeDraft($query)
    {
        return $query->where('is_posted', false);
    }

    /**
     * Scope: Par période
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    /**
     * Vérifier si écriture est équilibrée
     */
    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    /**
     * Empêcher modification si posté + création directe
     * 
     * GUARDS:
     * 1. creating: Bloque AccountingEntry::create() hors LedgerService
     * 2. updating: Bloque modification d'écriture postée
     * 3. deleting: Bloque suppression d'écriture postée
     */
    protected static function booted()
    {
        // GUARD 1: Interdire création directe (doit passer par LedgerService)
        static::creating(function ($entry) {
            if (!app()->bound('ledger.creating.allowed')) {
                throw new \Modules\Accounting\Exceptions\ForbiddenCreationException(
                    "AccountingEntry::create() interdit. Utiliser LedgerService."
                );
            }
        });

        // GUARD 2: Interdire modification si posté
        static::updating(function ($entry) {
            $wasPosted = $entry->getOriginal('is_posted');
            
            if ($wasPosted && $entry->isDirty() && !$entry->isDirty('updated_at')) {
                throw new \Exception("Écriture {$entry->entry_number} est postée (irréversible)");
            }
        });

        // GUARD 3: Interdire suppression si posté
        static::deleting(function ($entry) {
            if ($entry->is_posted) {
                throw new \Exception("Écriture {$entry->entry_number} est postée (irréversible)");
            }
        });
    }

}
