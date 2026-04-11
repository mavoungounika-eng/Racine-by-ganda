<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class CmsEvent extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'content', 'featured_image',
        'location', 'address', 'start_date', 'end_date', 'type',
        'status', 'capacity', 'price', 'is_free', 'registration_required', 'meta'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_free' => 'boolean',
        'registration_required' => 'boolean',
        'meta' => 'array',
        'price' => 'decimal:2',
    ];

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->where('status', 'upcoming')
            ->orderBy('start_date');
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where('status', 'ongoing');
    }

    public function getTypeLabelsAttribute(): array
    {
        return [
            'fashion_show' => 'Défilé de Mode',
            'exhibition' => 'Exposition',
            'workshop' => 'Atelier',
            'sale' => 'Vente Privée',
            'meeting' => 'Rencontre',
            'other' => 'Autre',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->typeLabels[$this->type] ?? $this->type;
    }
}

