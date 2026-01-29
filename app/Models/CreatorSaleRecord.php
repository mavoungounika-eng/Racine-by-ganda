<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle analytique pour le suivi des ventes créateurs.
 * 
 * ⚠️ Ce modèle est ANALYTIQUE uniquement.
 * RACINE n'encaisse pas les fonds décrits ici.
 */
class CreatorSaleRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'creator_id',
        'gross_amount',
        'payment_method',
        'status',
        'pickup_location',
        'fulfilled_by',
        'fulfilled_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fulfilled_at' => 'datetime',
    ];

    /**
     * Relation avec la commande d'origine.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation avec le profil créateur.
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class, 'creator_id');
    }

    /**
     * Relation avec le staff POS ayant validé la remise.
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }
}
