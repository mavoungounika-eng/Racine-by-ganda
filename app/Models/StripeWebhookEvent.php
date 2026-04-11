<?php

namespace App\Models;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_type',
        'payment_id',
        'checkout_session_id',
        'payment_intent_id',
        'status',
        'processed_at',
        'dispatched_at',
        'payload_hash',
        'requeue_count',
        'last_requeue_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'last_requeue_at' => 'datetime',
        'requeue_count' => 'integer',
    ];

    /**
     * Relation avec le Payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Vérifier si l'événement a déjà été traité
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['processed', 'ignored']);
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

    /**
     * Marquer l'événement comme traité
     * 
     * Méthode idempotente : peut être appelée plusieurs fois sans effet de bord.
     * Le payment_id est utilisé uniquement pour audit et traçabilité (référence Payment::id).
     * 
     * @param int|null $paymentId ID du Payment (payments table) si disponible et valide.
     *                            Ne sera utilisé que si payment_id est actuellement null.
     *                            Ne jamais écraser un payment_id existant.
     * @return void
     */
    public function markAsProcessed(?int $paymentId = null): void
    {
        $updateData = [];

        // Idempotence : ne pas réécrire status/processed_at si déjà finalisés
        if ($this->status !== 'processed') {
            $updateData['status'] = 'processed';
        }

        if ($this->processed_at === null) {
            $updateData['processed_at'] = now();
        }

        // Set payment_id UNIQUEMENT s'il est encore null et qu'un paymentId valide est fourni
        if ($this->payment_id === null && $paymentId !== null) {
            // Vérifier que le Payment existe réellement
            $payment = Payment::find($paymentId);
            if ($payment) {
                $updateData['payment_id'] = $paymentId;
            }
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    /**
     * Marquer l'événement comme ignoré
     */
    public function markAsIgnored(): void
    {
        $this->update([
            'status' => 'ignored',
            'processed_at' => now(),
        ]);
    }

    /**
     * Marquer l'événement comme échoué
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }
}
