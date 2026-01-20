<?php

namespace App\DTOs\Auth;

use App\Models\User;

/**
 * Authentication Result DTO
 * 
 * Represents the outcome of an authentication attempt.
 * Used by AuthOrchestratorService to communicate results to controllers.
 */
class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?string $redirectUrl = null,
        public readonly array $errors = [],
        public readonly ?string $challenge = null, // '2fa', 'captcha', or null
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Create a successful authentication result
     */
    public static function success(User $user, string $redirectUrl, ?string $challenge = null): self
    {
        return new self(
            success: true,
            user: $user,
            redirectUrl: $redirectUrl,
            challenge: $challenge,
        );
    }

    /**
     * Create a failed authentication result
     */
    public static function failed(array $errors, ?array $metadata = null): self
    {
        return new self(
            success: false,
            errors: $errors,
            metadata: $metadata,
        );
    }

    /**
     * Create a result requiring CAPTCHA
     */
    public static function captchaRequired(array $errors = []): self
    {
        return new self(
            success: false,
            errors: $errors ?: ['captcha' => 'CAPTCHA requis après plusieurs tentatives échouées.'],
            challenge: 'captcha',
        );
    }

    /**
     * Create a result requiring 2FA
     */
    public static function twoFactorRequired(User $user, string $redirectUrl): self
    {
        return new self(
            success: true,
            user: $user,
            redirectUrl: $redirectUrl,
            challenge: '2fa',
        );
    }

    /**
     * Check if authentication was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if authentication failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Check if CAPTCHA is required
     */
    public function requiresCaptcha(): bool
    {
        return $this->challenge === 'captcha';
    }

    /**
     * Check if 2FA is required
     */
    public function requires2FA(): bool
    {
        return $this->challenge === '2fa';
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstKey = array_key_first($this->errors);
        $firstError = $this->errors[$firstKey];

        return is_array($firstError) ? $firstError[0] : $firstError;
    }

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'user_id' => $this->user?->id,
            'redirect_url' => $this->redirectUrl,
            'errors' => $this->errors,
            'challenge' => $this->challenge,
            'metadata' => $this->metadata,
        ];
    }
}
