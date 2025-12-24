<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntryLine extends Model
{
    protected $table = 'accounting_entry_lines';

    protected $fillable = [
        'entry_id',
        'account_code',
        'line_number',
        'description',
        'debit',
        'credit',
        'amount_ht',
        'vat_amount',
        'vat_rate',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'amount_ht' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
    ];

    /**
     * Relation: Écriture comptable
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'entry_id');
    }

    /**
     * Relation: Compte
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'code');
    }

    /**
     * Obtenir le montant (débit ou crédit)
     */
    public function getAmountAttribute(): float
    {
        return $this->debit > 0 ? $this->debit : $this->credit;
    }

    /**
     * Vérifier si ligne est au débit
     */
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Vérifier si ligne est au crédit
     */
    public function isCredit(): bool
    {
        return $this->credit > 0;
    }

    /**
     * Vérifier si ligne a de la TVA
     */
    public function hasVat(): bool
    {
        return $this->vat_amount !== null && $this->vat_amount > 0;
    }
}
