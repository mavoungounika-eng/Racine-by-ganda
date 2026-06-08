<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_email',
        'new_email',
        'old_email_token',
        'new_email_token',
        'old_email_verified_at',
        'new_email_verified_at',
        'expires_at',
    ];

    protected $casts = [
        'old_email_verified_at' => 'datetime',
        'new_email_verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the email change request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if both emails have been verified.
     */
    public function isFullyVerified(): bool
    {
        return $this->old_email_verified_at !== null
            && $this->new_email_verified_at !== null;
    }

    /**
     * Check if the request has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Generate secure random tokens for both emails.
     */
    public static function generateTokens(): array
    {
        return [
            'old_email_token' => Str::random(64),
            'new_email_token' => Str::random(64),
        ];
    }

    /**
     * Scope to only include non-expired requests.
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to only include pending requests (not fully verified).
     */
    public function scopePending($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('old_email_verified_at')
              ->orWhereNull('new_email_verified_at');
        });
    }
}
