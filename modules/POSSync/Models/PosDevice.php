<?php

namespace Modules\POSSync\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PosDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'machine_id',
        'name',
        'machine_secret',
        'status',
        'last_sync_at',
        'version',
        'metadata',
        'blocked_at',
        'blocked_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    /**
     * Chiffrer machine_secret avant sauvegarde
     */
    public function setMachineSecretAttribute($value)
    {
        $this->attributes['machine_secret'] = Crypt::encryptString($value);
    }

    /**
     * Déchiffrer machine_secret à la lecture
     */
    public function getMachineSecretAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    /**
     * Relation vers les événements synchronisés
     */
    public function syncedEvents()
    {
        return $this->hasMany(PosSyncedEvent::class, 'machine_id', 'machine_id');
    }

    /**
     * Relation vers les logs de sync
     */
    public function syncLogs()
    {
        return $this->hasMany(PosSyncLog::class, 'machine_id', 'machine_id');
    }

    /**
     * Vérifier si le device est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si le device est bloqué
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Bloquer le device
     */
    public function block(string $reason): void
    {
        $this->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_reason' => $reason,
        ]);
    }

    /**
     * Débloquer le device
     */
    public function unblock(): void
    {
        $this->update([
            'status' => 'active',
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);
    }

    /**
     * Mettre à jour le timestamp de dernière sync
     */
    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Scope pour devices actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour devices bloqués
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }
}
