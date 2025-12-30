<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsFaqCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'order',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
    
    // Relations
    public function faqs(): HasMany
    {
        return $this->hasMany(CmsFaq::class, 'category_id')->orderBy('order');
    }
    
    public function activeFaqs(): HasMany
    {
        return $this->hasMany(CmsFaq::class, 'category_id')
                    ->where('is_active', true)
                    ->orderBy('order');
    }
    
    // Helpers
    public function getFaqCountAttribute(): int
    {
        return $this->faqs()->count();
    }
}

