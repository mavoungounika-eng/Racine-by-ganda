<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_at',
        'unread_count',
        'is_archived',
        'notifications_enabled',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'unread_count' => 'integer',
        'is_archived' => 'boolean',
        'notifications_enabled' => 'boolean',
    ];

    // Rôles
    const ROLE_SENDER = 'sender';
    const ROLE_RECIPIENT = 'recipient';
    const ROLE_ADMIN = 'admin';
    const ROLE_PARTICIPANT = 'participant';

    /**
     * Conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Utilisateur participant
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        $this->update([
            'last_read_at' => now(),
            'unread_count' => 0,
        ]);
    }

    /**
     * Incrémenter le compteur de non lus
     */
    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    /**
     * Réinitialiser le compteur de non lus
     */
    public function resetUnread(): void
    {
        $this->update(['unread_count' => 0]);
    }

    /**
     * Scope pour les participants non archivés
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope pour un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
