<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'action_url',
        'action_text',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Types de notifications disponibles
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';
    const TYPE_ORDER = 'order';
    const TYPE_STOCK = 'stock';
    const TYPE_SYSTEM = 'system';

    /**
     * Icônes par défaut selon le type
     */
    const DEFAULT_ICONS = [
        'info' => 'ℹ️',
        'success' => '✅',
        'warning' => '⚠️',
        'danger' => '🚨',
        'order' => '📦',
        'stock' => '📊',
        'system' => '⚙️',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope pour les notifications lues
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope récentes (dernières 24h)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Marquer comme non lu
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Obtenir l'icône (personnalisée ou par défaut)
     */
    public function getDisplayIconAttribute(): string
    {
        return $this->icon ?? self::DEFAULT_ICONS[$this->type] ?? '🔔';
    }

    /**
     * Formater pour l'affichage
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'display_icon' => $this->display_icon,
            'time_ago' => $this->created_at->diffForHumans(),
        ]);
    }
}

