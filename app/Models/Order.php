<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'creator_id',
        'address_id',
        'promo_code_id',
        'discount_amount',
        'shipping_method',
        'shipping_cost',
        'status',
        'payment_status',
        'payment_method',
        'total_amount',
        'original_total',
        'cancellation_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'qr_token',
        'order_number',
        'expected_delivery_date',
        'prepared_at',
        'shipped_at',
        'currency',
        'amount_eur',
    ];

    protected $casts = [
        'total_amount'          => 'decimal:2',
        'original_total'        => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'shipping_cost'         => 'decimal:2',
        'expected_delivery_date'=> 'datetime',
        'prepared_at'           => 'datetime',
        'shipped_at'            => 'datetime',
        'amount_eur'            => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->qr_token)) {
                $order->qr_token = static::generateUniqueQrToken();
            }
            if (empty($order->order_number)) {
                $orderNumberService = app(\App\Services\OrderNumberService::class);
                $order->order_number = $orderNumberService->generateOrderNumber();
            }
            // Mémoriser le montant original une seule fois à la création
            if (empty($order->original_total)) {
                $order->original_total = $order->total_amount;
            }
        });

        // GOVERNANCE C1 + C2 : Guards d'invariants critiques
        static::updating(function (Order $order) {
            // C1: Non-régression payment_status (paid → pending interdit)
            if ($order->isDirty('payment_status')) {
                $old = $order->getOriginal('payment_status');
                $new = $order->payment_status;
                if ($old === 'paid' && $new === 'pending') {
                    throw new \DomainException(
                        "INVARIANT VIOLATION: payment_status cannot regress from 'paid' to 'pending'. " .
                        "Order #{$order->id}. Use refund/compensation instead."
                    );
                }
            }

            // C2: États terminaux immuables — completed et archived uniquement.
            // 'cancelled' est un état DORMANT restaurable par le client.
            if ($order->isDirty('status')) {
                $old = $order->getOriginal('status');
                if (in_array($old, ['completed', 'archived'], true)) {
                    throw new \DomainException(
                        "INVARIANT VIOLATION: Order #{$order->id} status '{$old}' is terminal and cannot be modified."
                    );
                }
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public static function generateUniqueQrToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while (static::where('qr_token', $token)->exists());
        return $token;
    }

    /**
     * États vraiment terminaux — ne peuvent plus jamais évoluer.
     * 'cancelled' N'EST PAS terminal : dormant et restaurable.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'archived'], true);
    }

    /**
     * La commande est-elle dormante (annulée mais restaurable) ?
     */
    public function isDormant(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * A-t-on des items partiellement annulés (annulation partielle avant annulation globale) ?
     */
    public function hasPartialCancellation(): bool
    {
        return $this->cancellation_type === 'partial';
    }

    // ─── Calcul total ────────────────────────────────────────────────────────

    /**
     * Recalcule le total sur les items actifs (non-cancelled).
     * Si tous les items sont cancelled → total = 0 (correct métier).
     * shipping_cost et discount_amount sont inclus.
     * original_total reste toujours intact comme référence.
     */
    public function recalculateTotal(): void
    {
        $itemsTotal = $this->items()
            ->whereNotIn('status', [OrderItem::STATUS_CANCELLED])
            ->sum(\Illuminate\Support\Facades\DB::raw('price * quantity'));

        $net = max(0, $itemsTotal + (float) $this->shipping_cost - (float) $this->discount_amount);

        $this->update(['total_amount' => $net]);
    }

    // ─── Actions métier ──────────────────────────────────────────────────────

    /**
     * Annule la commande globalement sans toucher aux items individuellement.
     * cancellation_type = 'global' → à la restauration tous les items reviennent.
     */
    public function cancelGlobally(): void
    {
        if ($this->isTerminal()) {
            throw new \DomainException("Impossible d'annuler une commande terminale (#{$this->id}).");
        }
        $this->update([
            'status'            => 'cancelled',
            'cancellation_type' => 'global',
        ]);
        // Marquer les items actifs comme cancelled (pour recalcul cohérent)
        $this->items()
            ->whereNotIn('status', [OrderItem::STATUS_CANCELLED])
            ->update(['status' => OrderItem::STATUS_CANCELLED, 'cancelled_at' => now()]);
    }

    /**
     * Restaure une commande annulée.
     * - Si annulation globale : remet tous les items en 'restored'
     * - Si annulation partielle : laisse les items cancelled tels quels
     * Recalcule le total après restauration.
     */
    public function restore(): void
    {
        if ($this->status !== 'cancelled') {
            throw new \DomainException("Seule une commande annulée peut être restaurée (#{$this->id}).");
        }
        if ($this->cancellation_type === 'global') {
            $this->items()
                ->where('status', OrderItem::STATUS_CANCELLED)
                ->update(['status' => OrderItem::STATUS_RESTORED, 'cancelled_at' => null]);
        }
        $this->update([
            'status'            => 'pending',
            'cancellation_type' => null,
        ]);
        $this->refresh();
        $this->recalculateTotal();
    }

    /**
     * Supprime définitivement la commande de la vue client (soft delete).
     * Seul un admin peut voir les commandes archivées.
     */
    public function archivePermanently(): void
    {
        if (! $this->isDormant()) {
            throw new \DomainException("Seule une commande annulée peut être archivée définitivement (#{$this->id}).");
        }
        $this->update(['status' => 'archived']);
        $this->delete(); // soft delete via deleted_at
    }
}
