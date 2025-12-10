<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-génération du slug lors de la création
        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $slug = Str::slug($collection->name);
                $count = static::where('slug', 'like', "{$slug}%")
                    ->where('user_id', $collection->user_id)
                    ->count();
                
                $collection->slug = $count > 0 
                    ? "{$slug}-" . ($count + 1) 
                    : $slug;
            }
        });

        // Mise à jour du slug si le name change
        static::updating(function ($collection) {
            if ($collection->isDirty('name') && empty($collection->slug)) {
                $slug = Str::slug($collection->name);
                $count = static::where('slug', 'like', "{$slug}%")
                    ->where('user_id', $collection->user_id)
                    ->where('id', '!=', $collection->id)
                    ->count();
                
                $collection->slug = $count > 0 
                    ? "{$slug}-" . ($count + 1) 
                    : $slug;
            }
        });
    }

    /**
     * Get the user that owns the collection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the collection.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active collections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the URL for the collection's image.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image 
            ? asset('storage/collections/' . $this->image) 
            : null;
    }

    /**
     * Get the count of products in this collection.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}
