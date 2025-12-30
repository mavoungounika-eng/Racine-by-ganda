<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingBalance extends Model
{
    protected $table = 'accounting_balances';

    protected $fillable = [
        'fiscal_year_id',
        'account_code',
        'period_start',
        'period_end',
        'opening_balance_debit',
        'opening_balance_credit',
        'period_debit',
        'period_credit',
        'closing_balance_debit',
        'closing_balance_credit',
        'last_calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance_debit' => 'decimal:2',
        'opening_balance_credit' => 'decimal:2',
        'period_debit' => 'decimal:2',
        'period_credit' => 'decimal:2',
        'closing_balance_debit' => 'decimal:2',
        'closing_balance_credit' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Relation: Exercice comptable
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    /**
     * Relation: Compte
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'code');
    }

    /**
     * Calculer solde net
     */
    public function getNetBalanceAttribute(): float
    {
        return $this->closing_balance_debit - $this->closing_balance_credit;
    }
}
