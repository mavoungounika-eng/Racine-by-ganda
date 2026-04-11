<?php

namespace Modules\CMS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsBlock extends Model
{
    protected $fillable = [
        'name',
        'identifier',
        'type',
        'zone',
        'content',
        'settings',
        'page_slug',
        'is_active',
        'order',
        'updated_by',
    ];
    
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByIdentifier($query, string $identifier)
    {
        return $query->where('identifier', $identifier);
    }
    
    public function scopeByZone($query, string $zone)
    {
        return $query->where('zone', $zone)->orderBy('order');
    }
    
    public function scopeForPage($query, ?string $pageSlug = null)
    {
        return $query->where(function ($q) use ($pageSlug) {
            $q->whereNull('page_slug')
              ->orWhere('page_slug', $pageSlug);
        });
    }
    
    // Relations
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    // Helpers
    public static function get(string $identifier, $default = null)
    {
        $block = static::active()->byIdentifier($identifier)->first();
        return $block ? $block->content : $default;
    }
    
    public static function getJson(string $identifier, $default = [])
    {
        $block = static::active()->byIdentifier($identifier)->first();
        if (!$block) return $default;
        
        return json_decode($block->content, true) ?: $default;
    }
    
    public function getDecodedContentAttribute()
    {
        if ($this->type === 'json') {
            return json_decode($this->content, true);
        }
        return $this->content;
    }
}

