<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'route_name',
        'route_params',
        'icon',
        'target',
        'css_class',
        'order',
        'is_active',
    ];
    
    protected $casts = [
        'route_params' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Relations
    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'menu_id');
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsMenuItem::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('order');
    }
    
    // Helpers
    public function getUrlAttribute($value): string
    {
        // Si une URL directe est définie
        if ($value) {
            return $value;
        }
        
        // Si un nom de route est défini
        if ($this->route_name) {
            try {
                return route($this->route_name, $this->route_params ?? []);
            } catch (\Exception $e) {
                return '#';
            }
        }
        
        return '#';
    }
    
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
    
    public function isActive(): bool
    {
        $currentUrl = request()->url();
        $itemUrl = $this->url;
        
        // Vérifier l'URL exacte ou si c'est un parent
        if ($currentUrl === $itemUrl) {
            return true;
        }
        
        // Vérifier les enfants
        foreach ($this->children as $child) {
            if ($child->isActive()) {
                return true;
            }
        }
        
        return false;
    }
}

