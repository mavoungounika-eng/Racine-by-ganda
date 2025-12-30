<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonetbilCallbackEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key',
        'payment_ref',
        'transaction_id',
        'transaction_uuid',
        'event_type',
        'status',
        'payload',
        'error',
        'received_at',
        'processed_at',
        'dispatched_at',
        'requeue_count',
        'last_requeue_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'last_requeue_at' => 'datetime',
        'requeue_count' => 'integer',
    ];

    /**
     * Relation avec PaymentTransaction (via payment_ref)
     * Note : Relation optionnelle car payment_ref peut ne pas matcher directement
     */
    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_ref', 'payment_ref');
    }

    /**
     * Scope : Événements traités
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope : Événements en échec
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope : Événements en attente
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['received', 'ignored']);
    }

    /**
     * Vérifier si l'événement est bloqué (limite requeue atteinte)
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Marquer l'événement comme bloqué
     */
    public function markAsBlocked(): void
    {
        $this->update([
            'status' => 'blocked',
        ]);
    }
}




