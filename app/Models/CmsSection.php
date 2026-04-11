<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle CmsSection - Sections de contenu par page CMS
 * 
 * Gère les sections de contenu (hero, intro, body, etc.) pour chaque page.
 */
class CmsSection extends Model
{
    protected $fillable = [
        'page_slug',
        'key',
        'type',
        'data',
        'is_active',
        'order',
    ];

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relation vers la page CMS
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_slug', 'slug');
    }

    /**
     * Scope pour récupérer uniquement les sections actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour récupérer les sections d'une page spécifique
     */
    public function scopeForPage($query, string $pageSlug)
    {
        return $query->where('page_slug', $pageSlug);
    }

    /**
     * Scope pour ordonner par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Récupérer une valeur spécifique depuis le JSON data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}

