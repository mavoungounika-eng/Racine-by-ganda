<?php

namespace Modules\CMS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CmsMedia extends Model
{
    protected $table = 'cms_media';
    
    protected $fillable = [
        'name',
        'file_name',
        'file_path',
        'mime_type',
        'size',
        'disk',
        'width',
        'height',
        'folder',
        'alt_text',
        'caption',
        'tags',
        'uploaded_by',
    ];
    
    protected $casts = [
        'tags' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];
    
    // Scopes
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }
    
    public function scopeInFolder($query, string $folder)
    {
        return $query->where('folder', $folder);
    }
    
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
    
    // Relations
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    // Helpers
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }
    
    public function getThumbnailUrlAttribute(): string
    {
        // Si un thumbnail existe
        $thumbPath = str_replace(
            $this->file_name,
            'thumb_' . $this->file_name,
            $this->file_path
        );
        
        if (Storage::disk($this->disk)->exists($thumbPath)) {
            return Storage::disk($this->disk)->url($thumbPath);
        }
        
        return $this->url;
    }
    
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
    
    public function delete()
    {
        // Supprimer le fichier physique
        Storage::disk($this->disk)->delete($this->file_path);
        
        // Supprimer le thumbnail si existe
        $thumbPath = str_replace(
            $this->file_name,
            'thumb_' . $this->file_name,
            $this->file_path
        );
        Storage::disk($this->disk)->delete($thumbPath);
        
        return parent::delete();
    }
}

