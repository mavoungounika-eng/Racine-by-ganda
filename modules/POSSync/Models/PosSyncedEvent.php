<?php

namespace Modules\POSSync\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PosSyncedEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'machine_id',
        'event_uuid',
        'event_type',
        'version',
        'payload',
        'signature',
        'occurred_at',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'synced_at' => 'datetime',
        'version' => 'integer',
    ];

    /**
     * Relation vers le device POS
     */
    public function device()
    {
        return $this->belongsTo(PosDevice::class, 'machine_id', 'machine_id');
    }

    /**
     * Scope pour filtrer par machine
     */
    public function scopeForMachine($query, string $machineId)
    {
        return $query->where('machine_id', $machineId);
    }

    /**
     * Scope pour filtrer par type d'événement
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
