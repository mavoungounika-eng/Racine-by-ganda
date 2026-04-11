<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class FiscalYear extends Model
{
    protected $table = 'accounting_fiscal_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * Relation: Utilisateur ayant clôturé
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Relation: Écritures comptables
     */
    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'fiscal_year_id');
    }

    /**
     * Relation: Balances
     */
    public function balances(): HasMany
    {
        return $this->hasMany(AccountingBalance::class, 'fiscal_year_id');
    }

    /**
     * Scope: Exercices ouverts
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Scope: Exercice en cours
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}
