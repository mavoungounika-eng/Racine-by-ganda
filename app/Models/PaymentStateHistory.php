<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStateHistory extends Model
{
    protected $fillable = [
        'payment_id',
        'payment_type',
        'from_state',
        'to_state',
        'trigger',
        'metadata',
        'reason',
        'is_valid',
        'validation_error',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $table = 'payment_state_histories';

    /**
     * Get the payment related to this state history.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

    /**
     * Scope: Get valid transitions only.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope: Get transitions TO a specific state.
     */
    public function scopeToState($query, string $state)
    {
        return $query->where('to_state', $state);
    }

    /**
     * Scope: Get transitions FROM a specific state.
     */
    public function scopeFromState($query, string $state)
    {
        return $query->where('from_state', $state);
    }

    /**
     * Scope: Get transitions by trigger type.
     */
    public function scopeByTrigger($query, string $trigger)
    {
        return $query->where('trigger', $trigger);
    }

    /**
     * Scope: Get latest state for payment.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Get the current state of a payment.
     */
    public static function currentStateOf(string $paymentId): ?string
    {
        $latest = self::where('payment_id', $paymentId)
            ->orderByDesc('created_at')
            ->first();

        return $latest?->to_state;
    }

    /**
     * Get state transition history for a payment.
     */
    public static function historyOf(string $paymentId)
    {
        return self::where('payment_id', $paymentId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Check if state change happened in last N seconds.
     */
    public function isRecent(int $seconds = 60): bool
    {
        return $this->created_at->diffInSeconds(now()) <= $seconds;
    }
}
