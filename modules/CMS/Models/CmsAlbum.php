<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class CmsAlbum extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'cover_image', 'photos',
        'category', 'album_date', 'status', 'order', 'is_featured'
    ];

    protected $casts = [
        'photos' => 'array',
        'album_date' => 'date',
        'is_featured' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->orderBy('order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getPhotoCountAttribute(): int
    {
        return count($this->photos ?? []);
    }
}

