<?php

namespace App\Models;

use App\Exceptions\InvalidOrderItemTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    const STATUS_ACTIVE           = 'active';
    const STATUS_CANCELLED        = 'cancelled';
    const STATUS_RESTORED         = 'restored';
    const STATUS_CONFIRMED        = 'confirmed';
    const STATUS_SHIPPED          = 'shipped';
    const STATUS_DELIVERED        = 'delivered';
    const STATUS_DISPUTED         = 'disputed';
    const STATUS_RETURN_REQUESTED = 'return_requested';
    const STATUS_REFUNDED         = 'refunded';

    const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_CANCELLED,
        self::STATUS_RESTORED,
        self::STATUS_CONFIRMED,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_DISPUTED,
        self::STATUS_RETURN_REQUESTED,
        self::STATUS_REFUNDED,
    ];

    // Allowed transitions: [from => [allowed tos]]
    const TRANSITIONS = [
        self::STATUS_ACTIVE           => [self::STATUS_CANCELLED, self::STATUS_CONFIRMED, self::STATUS_SHIPPED],
        self::STATUS_RESTORED         => [self::STATUS_CANCELLED, self::STATUS_CONFIRMED, self::STATUS_SHIPPED],
        self::STATUS_CONFIRMED        => [self::STATUS_CANCELLED, self::STATUS_SHIPPED],
        self::STATUS_CANCELLED        => [self::STATUS_RESTORED],
        self::STATUS_SHIPPED          => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED        => [self::STATUS_DISPUTED, self::STATUS_RETURN_REQUESTED],
        self::STATUS_DISPUTED         => [self::STATUS_REFUNDED],
        self::STATUS_RETURN_REQUESTED => [self::STATUS_REFUNDED, self::STATUS_DELIVERED],
        self::STATUS_REFUNDED         => [],
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'status',
        'cancelled_at',
        'previous_cancellation_id',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function previousCancellation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_cancellation_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeRestored($query)
    {
        return $query->where('status', self::STATUS_RESTORED);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeShipped($query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', self::STATUS_DISPUTED);
    }

    public function scopeReturnRequested($query)
    {
        return $query->where('status', self::STATUS_RETURN_REQUESTED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    // ─── Transition helpers ───────────────────────────────────────────────────

    private function transition(string $to): void
    {
        $from = $this->status ?? self::STATUS_ACTIVE;

        $allowed = self::TRANSITIONS[$from] ?? [];
        if (!in_array($to, $allowed, true)) {
            throw new InvalidOrderItemTransitionException($from, $to);
        }

        $data = ['status' => $to];

        if ($to === self::STATUS_CANCELLED) {
            $data['cancelled_at'] = now();
        }

        $this->update($data);
    }

    // ─── Public transition methods ────────────────────────────────────────────

    public function cancel(): void
    {
        $this->transition(self::STATUS_CANCELLED);
    }

    public function restore(): void
    {
        $this->transition(self::STATUS_RESTORED);
    }

    public function markConfirmed(): void
    {
        $this->transition(self::STATUS_CONFIRMED);
    }

    public function markShipped(): void
    {
        $this->transition(self::STATUS_SHIPPED);
    }

    public function markDelivered(): void
    {
        $this->transition(self::STATUS_DELIVERED);
    }

    public function requestReturn(): void
    {
        $this->transition(self::STATUS_RETURN_REQUESTED);
    }

    public function markRefunded(): void
    {
        $this->transition(self::STATUS_REFUNDED);
    }
}
