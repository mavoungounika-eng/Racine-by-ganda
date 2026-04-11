<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_payment_id',
        'status',
        'amount',
        'currency',
        'channel',
        'customer_phone',
        'external_reference',
        'metadata',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * ✅ CORRECTION 7 : Vérifier si le paiement est dans un état terminal (immuable)
     * 
     * Les états terminaux ne peuvent plus être modifiés :
     * - paid : Paiement confirmé
     * - cancelled : Paiement annulé
     * 
     * @return bool True si le paiement est dans un état terminal
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['paid', 'cancelled'], true);
    }
}
