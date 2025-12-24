<?php

namespace Modules\POSSync\Models;

use Illuminate\Database\Eloquent\Model;

class PosSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'event_uuid',
        'action',
        'details',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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
     * Scope pour filtrer par action
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour logs récents
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
