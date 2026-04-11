<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ERP\Models\ErpProductDetail;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'collection_id',
        'user_id',
        'product_type',
        'title',
        'slug',
        'description',
        'price',
        'stock',
        'is_active',
        'main_image',
        'ai_description',
        'ai_price_suggestion',
        'ai_last_analyzed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'ai_price_suggestion' => 'decimal:2',
        'ai_last_analyzed_at' => 'datetime',
    ];

    /**
     * Backward compatibility alias: legacy code often reads/writes "name".
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['title'] = $value;
    }

    /**
     * Backward compatibility alias for legacy "name" reads.
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['title'] ?? '');
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the collection that owns the product.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the creator (user) that owns the product.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the stock alerts for this product.
     */
    public function stockAlerts()
    {
        return $this->hasMany(StockAlert::class);
    }

    /**
     * Get active stock alerts for this product.
     */
    public function activeStockAlerts()
    {
        return $this->hasMany(StockAlert::class)->where('status', 'active');
    }

    /**
     * Check if product has low stock (default threshold: 10).
     */
    public function hasLowStock(int $threshold = 10): bool
    {
        return $this->stock > 0 && $this->stock <= $threshold;
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Get the reviews for this product.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get the average rating for this product.
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get the total number of reviews.
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Get the wishlist entries for this product.
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Check if a specific user has this product in their wishlist.
     */
    public function isInWishlist(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return $this->wishlists()->where('user_id', $userId)->exists();
    }

    /**
     * Scope a query to only include brand products.
     */
    public function scopeBrand($query)
    {
        return $query->where('product_type', 'brand');
    }

    /**
     * Scope a query to only include marketplace products.
     */
    public function scopeMarketplace($query)
    {
        return $query->where('product_type', 'marketplace');
    }

    /**
     * Check if product is a brand product (RACINE BY GANDA).
     */
    public function isBrand(): bool
    {
        return $this->product_type === 'brand';
    }

    /**
     * Check if product is a marketplace product (creator).
     */
    public function isMarketplace(): bool
    {
        return $this->product_type === 'marketplace';
    }

    /**
     * Get the vendor name for this product.
     */
    public function getVendorNameAttribute(): string
    {
        if ($this->isBrand()) {
            return 'RACINE BY GANDA';
        }

        return $this->creator?->creatorProfile?->brand_name ?? 'Créateur partenaire';
    }

    /**
     * Get the ERP product details (SKU, barcode, etc.)
     */
    public function erpDetails(): HasOne
    {
        return $this->hasOne(ErpProductDetail::class, 'product_id');
    }

    /**
     * Get the SKU of the product
     */
    public function getSkuAttribute(): ?string
    {
        return $this->erpDetails?->sku;
    }

    /**
     * Get the barcode of the product
     */
    public function getBarcodeAttribute(): ?string
    {
        return $this->erpDetails?->barcode;
    }

    /**
     * Get all images for this product (ordered)
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    /**
     * Get the main image for this product
     */
    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    /**
     * Get the first image (fallback if no main image)
     */
    public function getFirstImageAttribute(): ?ProductImage
    {
        return $this->mainImage ?? $this->images->first();
    }

    /**
     * Get the order items associated with the product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
