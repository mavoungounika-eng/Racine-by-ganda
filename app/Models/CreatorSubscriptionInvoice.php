<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorSubscriptionInvoice extends Model
{
    protected $table = 'creator_subscription_invoices';

    protected $fillable = [
        'creator_subscription_id',
        'stripe_invoice_id',
        'stripe_charge_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'due_date',
        'hosted_invoice_url',
        'invoice_pdf',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CreatorSubscription::class, 'creator_subscription_id');
    }
}
