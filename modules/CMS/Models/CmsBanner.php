<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class CmsBanner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'image_mobile', 'link_url',
        'link_text', 'position', 'order', 'is_active', 'start_date', 'end_date'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->orderBy('order');
    }

    public function scopeForPosition($query, string $position)
    {
        return $query->where('position', $position);
    }
}
