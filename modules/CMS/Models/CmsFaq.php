<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsFaq extends Model
{
    protected $table = 'cms_faqs';
    
    protected $fillable = [
        'category_id',
        'question',
        'answer',
        'order',
        'is_featured',
        'is_active',
        'views',
        'helpful_yes',
        'helpful_no',
    ];
    
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'views' => 'integer',
        'helpful_yes' => 'integer',
        'helpful_no' => 'integer',
    ];
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
    
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }
    
    public function scopePopular($query, int $limit = 10)
    {
        return $query->where('is_active', true)
                     ->orderByDesc('views')
                     ->limit($limit);
    }
    
    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsFaqCategory::class, 'category_id');
    }
    
    // Helpers
    public function incrementViews(): void
    {
        $this->increment('views');
    }
    
    public function markHelpful(bool $helpful): void
    {
        if ($helpful) {
            $this->increment('helpful_yes');
        } else {
            $this->increment('helpful_no');
        }
    }
    
    public function getHelpfulPercentageAttribute(): int
    {
        $total = $this->helpful_yes + $this->helpful_no;
        if ($total === 0) return 0;
        
        return (int) round(($this->helpful_yes / $total) * 100);
    }
}

