<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CmsPage extends Model
{
    protected $table = 'pages';
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'template', 'status', 'order', 'created_by', 'published_at',
        'meta_title', 'meta_description', 'seo_title', 'seo_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function getUrlAttribute(): string
    {
        return route('cms.page.show', $this->slug);
    }
}
