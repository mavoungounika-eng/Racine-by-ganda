<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'enabled',
        'enabled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recovery_codes' => 'array',
        'enabled' => 'boolean',
        'enabled_at' => 'datetime',
    ];

    /**
     * Get the user that owns the 2FA.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get decrypted secret.
     */
    public function getDecryptedSecret(): string
    {
        return decrypt($this->secret);
    }

    /**
     * Get decrypted recovery codes.
     */
    public function getDecryptedRecoveryCodes(): array
    {
        return array_map('decrypt', $this->recovery_codes);
    }

    /**
     * Set encrypted secret.
     */
    public function setEncryptedSecret(string $secret): void
    {
        $this->secret = encrypt($secret);
    }

    /**
     * Set encrypted recovery codes.
     */
    public function setEncryptedRecoveryCodes(array $codes): void
    {
        $this->recovery_codes = array_map('encrypt', $codes);
    }
}
