<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationProductTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'product_id',
        'tagged_by',
        'note',
    ];

    /**
     * Conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Produit tagué
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Utilisateur qui a tagué
     */
    public function taggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }
}
