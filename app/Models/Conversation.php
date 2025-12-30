<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'subject',
        'related_order_id',
        'related_product_id',
        'created_by',
        'last_message_at',
        'is_archived',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    // Types de conversations
    const TYPE_DIRECT = 'direct';
    const TYPE_ORDER_THREAD = 'order_thread';
    const TYPE_PRODUCT_THREAD = 'product_thread';

    /**
     * Créateur de la conversation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Participants de la conversation
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Utilisateurs participants (via participants)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at', 'unread_count', 'is_archived', 'notifications_enabled'])
            ->withTimestamps();
    }

    /**
     * Messages de la conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Dernier message
     */
    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latestOfMany();
    }

    /**
     * Commande liée (si thread de commande)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    /**
     * Produit lié (si thread de produit)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    /**
     * Produits tagués dans cette conversation
     */
    public function taggedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'conversation_product_tags')
            ->withPivot(['tagged_by', 'note', 'created_at'])
            ->withTimestamps();
    }

    /**
     * Scope pour les conversations non archivées
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope pour un type de conversation
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour une commande
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('type', self::TYPE_ORDER_THREAD)
            ->where('related_order_id', $orderId);
    }

    /**
     * Scope pour un produit
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('type', self::TYPE_PRODUCT_THREAD)
            ->where('related_product_id', $productId);
    }

    /**
     * Mettre à jour la date du dernier message
     */
    public function updateLastMessageAt(): void
    {
        $lastMessage = $this->messages()->latest()->first();
        if ($lastMessage) {
            $this->update(['last_message_at' => $lastMessage->created_at]);
        }
    }

    /**
     * Obtenir le nombre de messages non lus pour un utilisateur
     */
    public function getUnreadCountForUser(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        return $participant ? $participant->unread_count : 0;
    }
}
