<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BankReconciliation extends Model
{
    protected $table = 'accounting_bank_reconciliations';

    protected $fillable = [
        'bank_account_code',
        'entry_id',
        'transaction_reference',
        'transaction_date',
        'amount',
        'status',
        'reconciled_at',
        'reconciled_by',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Relation: Compte bancaire
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_code', 'code');
    }

    /**
     * Relation: Écriture comptable
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'entry_id');
    }

    /**
     * Relation: Utilisateur ayant rapproché
     */
    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Scope: Rapprochements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Rapprochements validés
     */
    public function scopeReconciled($query)
    {
        return $query->where('status', 'reconciled');
    }
}
