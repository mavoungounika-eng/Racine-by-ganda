<?php

namespace App\Support\Privacy;

/**
 * SensitiveDataMasker - Protection des données PII
 * 
 * Masque les informations sensibles avant enregistrement en base :
 * - Mots de passe
 * - Emails (partiel)
 * - Numéros de carte
 * - Codes OTP / 2FA
 */
class SensitiveDataMasker
{
    /**
     * Liste des champs à masquer systématiquement
     */
    protected static array $redactedFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'otp_code',
        'secret',
        'token',
        'card_number',
        'cvv',
        'cvc',
        'api_key',
        'webhook_secret',
    ];

    /**
     * Masquer les données sensibles dans un array
     */
    public static function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::mask($value);
                continue;
            }

            if (self::shouldRedact($key)) {
                $data[$key] = '[REDACTED]';
            } elseif ($key === 'email' && !empty($value)) {
                $data[$key] = self::maskEmail($value);
            }
        }

        return $data;
    }

    /**
     * Vérifier si un champ doit être masqué
     */
    protected static function shouldRedact(string $key): bool
    {
        return in_array(strtolower($key), self::$redactedFields) 
            || str_contains($key, 'password')
            || str_contains($key, 'secret');
    }

    /**
     * Masquer partiellement un email (ex: a***b@domain.com)
     */
    protected static function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        [$local, $domain] = explode('@', $email);
        $len = strlen($local);
        
        if ($len <= 2) {
            return '*@' . $domain;
        }

        return $local[0] . str_repeat('*', $len - 2) . $local[$len - 1] . '@' . $domain;
    }
}
