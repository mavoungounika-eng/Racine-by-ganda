<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'original_name',
        'file_size',
        'mime_type',
        'file_type',
        'width',
        'height',
        'thumbnail_path',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    // Types de fichiers
    const TYPE_IMAGE = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_FILE = 'file';

    /**
     * Message
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Obtenir l'URL du fichier
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Obtenir l'URL du thumbnail (si image)
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }
        return null;
    }

    /**
     * Vérifier si c'est une image
     */
    public function isImage(): bool
    {
        return $this->file_type === self::TYPE_IMAGE || str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Obtenir la taille formatée
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
