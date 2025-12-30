<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PaymentTransactionFactory::new();
    }

    protected $fillable = [
        'provider',
        'order_id',
        'payment_ref',
        'item_ref',
        'transaction_id',
        'transaction_uuid',
        'amount',
        'currency',
        'status',
        'operator',
        'phone',
        'fee',
        'raw_payload',
        'notified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'raw_payload' => 'array',
        'notified_at' => 'datetime',
    ];

    /**
     * Relation avec la commande
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Vérifier si la transaction est déjà en succès (idempotence)
     * 
     * Utilise 'succeeded' (statut standardisé) au lieu de 'success'
     */
    public function isAlreadySuccessful(): bool
    {
        return $this->status === 'succeeded';
    }
}
