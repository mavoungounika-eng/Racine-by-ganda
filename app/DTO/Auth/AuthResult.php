<?php

namespace App\DTO\Auth;

use App\Models\User;
/**
 * DTO representing the result of an authentication attempt.
 */
class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?string $redirectUrl = null,
        public readonly array $errors = [],
        public readonly ?string $challenge = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function success(
        User $user,
        string $redirectUrl,
        ?string $challenge = null,
        ?array $metadata = null
    ): self {
        return new self(
            success: true,
            user: $user,
            redirectUrl: $redirectUrl,
            challenge: $challenge,
            metadata: $metadata,
        );
    }

    public static function failed(array $errors, ?array $metadata = null): self
    {
        return new self(
            success: false,
            errors: $errors,
            metadata: $metadata,
        );
    }

    public static function failure(array $errors, ?array $metadata = null): self
    {
        return self::failed($errors, $metadata);
    }

    public static function captchaRequired(?array $metadata = null): self
    {
        return new self(
            success: false,
            errors: ['captcha' => 'Veuillez valider le CAPTCHA.'],
            challenge: 'captcha',
            metadata: $metadata,
        );
    }

    public static function twoFactorRequired(
        User $user,
        string $redirectUrl,
        ?array $metadata = null
    ): self {
        return new self(
            success: true,
            user: $user,
            redirectUrl: $redirectUrl,
            challenge: '2fa',
            metadata: $metadata,
        );
    }

    public function requiresCaptcha(): bool
    {
        return $this->challenge === 'captcha';
    }

    public function requires2FA(): bool
    {
        return $this->challenge === '2fa';
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return !$this->success;
    }

    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $errors = $this->errors;
        $first = reset($errors);

        if (is_array($first)) {
            return (string) (reset($first) ?: '');
        }

        return (string) $first;
    }

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
