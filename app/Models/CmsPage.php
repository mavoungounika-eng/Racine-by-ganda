<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsPage extends Model
{
    protected $fillable = [
        'slug', 'title', 'type', 'template', 'seo_title', 'seo_description', 'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class, 'page_slug', 'slug')
            ->where('is_active', true)->orderBy('order');
    }

    public function section(string $key): ?CmsSection
    {
        return $this->sections()->where('key', $key)->where('is_active', true)->first();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}
