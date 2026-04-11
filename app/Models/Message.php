<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'read_by',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'read_by' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    // Types de messages
    const TYPE_TEXT = 'text';
    const TYPE_SYSTEM = 'system';
    const TYPE_ATTACHMENT = 'attachment';

    /**
     * Conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Expéditeur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pièces jointes
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Marquer comme lu par un utilisateur
     */
    public function markAsReadBy(int $userId): void
    {
        $readBy = $this->read_by ?? [];
        $readBy[$userId] = now()->toIso8601String();
        $this->update(['read_by' => $readBy]);
    }

    /**
     * Vérifier si lu par un utilisateur
     */
    public function isReadBy(int $userId): bool
    {
        $readBy = $this->read_by ?? [];
        return isset($readBy[$userId]);
    }

    /**
     * Marquer comme édité
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Scope pour les messages non supprimés
     */
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope pour un type de message
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
