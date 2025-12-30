<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartOfAccount extends Model
{
    protected $table = 'accounting_chart_of_accounts';

    protected $fillable = [
        'code',
        'label',
        'account_type',
        'parent_code',
        'normal_balance',
        'is_active',
        'is_system',
        'requires_vat',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'requires_vat' => 'boolean',
    ];

    /**
     * Relation: Compte parent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_code', 'code');
    }

    /**
     * Relation: Comptes enfants
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_code', 'code');
    }

    /**
     * Relation: Lignes d'écriture
     */
    public function entryLines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class, 'account_code', 'code');
    }

    /**
     * Relation: Balances
     */
    public function balances(): HasMany
    {
        return $this->hasMany(AccountingBalance::class, 'account_code', 'code');
    }

    /**
     * Scope: Comptes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Vérifier si compte est débiteur
     */
    public function isDebitAccount(): bool
    {
        return $this->normal_balance === 'debit';
    }

    /**
     * Vérifier si compte est créditeur
     */
    public function isCreditAccount(): bool
    {
        return $this->normal_balance === 'credit';
    }
}
