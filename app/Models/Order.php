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
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
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
    protected static function generateUniqueQrToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }
}
