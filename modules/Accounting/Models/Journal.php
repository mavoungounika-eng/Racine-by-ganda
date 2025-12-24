<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $table = 'accounting_journals';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation: Écritures comptables
     */
    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'journal_id');
    }

    /**
     * Scope: Journaux actifs
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
        return $query->where('type', $type);
    }
}
