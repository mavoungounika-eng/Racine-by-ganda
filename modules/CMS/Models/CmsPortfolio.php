<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPortfolio extends Model
{
    protected $table = 'cms_portfolio';
    
    protected $fillable = [
        'title', 'slug', 'description', 'content', 'featured_image',
        'gallery', 'category', 'client', 'project_date', 'tags', 'status', 'order'
    ];

    protected $casts = [
        'gallery' => 'array',
        'tags' => 'array',
        'project_date' => 'date',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->orderBy('order');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

