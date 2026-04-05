<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

/**
 * Modèle Page - Pages CMS universelles
 */
class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'status',
        'template',
        'show_in_footer',
        'show_in_header',
        'sort_order',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'show_in_footer' => 'boolean',
        'show_in_header' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Get the URL for the page.
     */
    public function getUrlAttribute(): string
    {
        return route('frontend.page.show', ['slug' => $this->slug]);
    }

    /**
     * Scope for published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope for pages to show in footer.
     */
    public function scopeForFooter($query)
    {
        return $query->where('show_in_footer', true)
                     ->orderBy('sort_order', 'asc');
    }

    /**
     * Scope for pages to show in header.
     */
    public function scopeForHeader($query)
    {
        return $query->where('show_in_header', true)
                     ->orderBy('sort_order', 'asc');
    }

    /**
     * User who created the page.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the page.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * CMS sections associated with this page (via slug).
     */
    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class, 'page_slug', 'slug');
    }

    /**
     * Get a specific section by key.
     */
    public function section(string $key): ?CmsSection
    {
        return $this->sections()->where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Accessor: seo_title falls back to meta_title.
     */
    public function getSeoTitleAttribute(): ?string
    {
        return $this->meta_title;
    }
}
