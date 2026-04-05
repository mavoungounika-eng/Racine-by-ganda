<?php

namespace App\Services\Auth\DTO;

use App\Models\User;

/**
 * Data Transfer Object pour le résultat d'authentification
 * 
 * Encapsule le résultat complet d'une tentative d'authentification,
 * incluant le succès, l'utilisateur, la destination, les erreurs,
 * et les challenges éventuels (2FA, CAPTCHA).
 */
class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?UserContext $context = null,
        public readonly ?string $redirectUrl = null,
        public readonly array $errors = [],
        public readonly ?string $challenge = null, // '2fa', 'captcha', etc.
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Créer un résultat de succès
     */
    public static function success(
        User $user,
        UserContext $context,
        string $redirectUrl,
        ?array $metadata = null
    ): self {
        return new self(
            success: true,
            user: $user,
            context: $context,
            redirectUrl: $redirectUrl,
            metadata: $metadata,
        );
    }

    /**
     * Créer un résultat d'échec
     */
    public static function failure(array $errors, ?array $metadata = null): self
    {
        return new self(
            success: false,
            errors: $errors,
            metadata: $metadata,
        );
    }

    /**
     * Créer un résultat avec challenge requis
     */
    public static function challenge(
        string $challenge,
        User $user,
        ?array $metadata = null
    ): self {
        return new self(
            success: false,
            user: $user,
            challenge: $challenge,
            metadata: $metadata,
        );
    }

    /**
     * Vérifier si l'authentification a réussi
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Vérifier si un challenge est requis
     */
    public function requiresChallenge(): bool
    {
        return $this->challenge !== null;
    }

    /**
     * Obtenir le type de challenge
     */
    public function getChallengeType(): ?string
    {
        return $this->challenge;
    }

    /**
     * Obtenir la route du challenge
     */
    public function getChallengeRoute(): ?string
    {
        return match($this->challenge) {
            '2fa' => '2fa.challenge',
            'captcha' => 'login', // Retour au login avec CAPTCHA
            default => null,
        };
    }

    /**
     * Vérifier si des erreurs sont présentes
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Obtenir les erreurs
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtenir une métadonnée spécifique
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
