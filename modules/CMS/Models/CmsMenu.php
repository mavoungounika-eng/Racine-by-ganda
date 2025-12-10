<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenu extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // Locations disponibles
    const LOCATIONS = [
        'header' => 'Menu principal (Header)',
        'footer' => 'Menu pied de page',
        'footer_links' => 'Liens footer',
        'mobile' => 'Menu mobile',
        'sidebar' => 'Sidebar',
    ];
    
    // Relations
    public function items(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')
                    ->whereNull('parent_id')
                    ->orderBy('order');
    }
    
    public function allItems(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')->orderBy('order');
    }
    
    // Helpers
    public static function getByLocation(string $location)
    {
        return static::where('location', $location)
                     ->where('is_active', true)
                     ->with(['items' => function ($q) {
                         $q->where('is_active', true)
                           ->with('children')
                           ->orderBy('order');
                     }])
                     ->first();
    }
    
    public function getItemsTree()
    {
        return $this->items()
                    ->where('is_active', true)
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('order');
                    }])
                    ->get();
    }
}

