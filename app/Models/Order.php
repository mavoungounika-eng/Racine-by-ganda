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
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'expected_delivery_date' => 'datetime',
        'prepared_at' => 'datetime',
        'shipped_at' => 'datetime',
        'amount_eur' => 'decimal:2',
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
        });

        // ✅ GOVERNANCE C1 + C2 : Guards d'invariants critiques
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

            // C2: États terminaux immuables (completed/cancelled)
            if ($order->isDirty('status')) {
                $old = $order->getOriginal('status');
                
                if (in_array($old, ['completed', 'cancelled'], true)) {
                    throw new \DomainException(
                        "INVARIANT VIOLATION: Order #{$order->id} status '{$old}' is terminal and cannot be modified."
                    );
                }
            }
        });
    }

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

    /**
     * Generate a unique QR token for the order
     */
    public static function generateUniqueQrToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * ✅ CORRECTION 7 : Vérifier si la commande est dans un état terminal (immuable)
     * 
     * Les états terminaux ne peuvent plus être modifiés :
     * - paid : Paiement confirmé (obsolète, utiliser payment_status='paid')
     * - cancelled : Commande annulée
     * - completed : Commande livrée
     * 
     * @return bool True si la commande est dans un état terminal
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['paid', 'cancelled', 'completed'], true);
    }

    public function recalculateTotal(): void
    {
        // Commande annulée/archivée : on conserve le total_amount original
        if (in_array($this->status, ['cancelled', 'archived'])) {
            return;
        }

        $this->update([
            'total_amount' => $this->items()
                ->where('status', '!=', OrderItem::STATUS_CANCELLED)
                ->sum(\Illuminate\Support\Facades\DB::raw('price * quantity')),
        ]);
    }
}
