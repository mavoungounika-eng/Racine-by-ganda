<?php

namespace App\Services\Payments;

/**
 * Service pour redacter (masquer) les secrets dans les payloads
 * 
 * Appliqué systématiquement avant affichage dans l'UI ou logs
 */
class PayloadRedactionService
{
    /**
     * Patterns de champs sensibles à masquer
     */
    private const SENSITIVE_PATTERNS = [
        'secret',
        'key',
        'token',
        'password',
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'authorization',
        'signature',
        'webhook_secret',
        'private_key',
    ];

    /**
     * Patterns de valeurs sensibles (début de chaîne)
     */
    private const SENSITIVE_VALUE_PATTERNS = [
        'sk_',           // Stripe secret key
        'pk_',           // Stripe public key (masqué aussi par précaution)
        'whsec_',        // Stripe webhook secret
        'sk-ant-',       // Anthropic API key
        'sk-proj-',      // Anthropic API key (project)
    ];

    /**
     * Redacter un payload (array ou JSON string)
     *
     * @param array|string $payload
     * @return array
     */
    public function redact($payload): array
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?? [];
        }

        if (!is_array($payload)) {
            return [];
        }

        return $this->redactArray($payload);
    }

    /**
     * Redacter un array récursivement
     *
     * @param array $data
     * @return array
     */
    private function redactArray(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);

            // Vérifier si la clé est sensible
            if ($this->isSensitiveKey($keyLower)) {
                $redacted[$key] = $this->maskValue($value);
                continue;
            }

            // Vérifier si la valeur est sensible (pattern)
            if (is_string($value) && $this->isSensitiveValue($value)) {
                $redacted[$key] = $this->maskValue($value);
                continue;
            }

            // Récursion pour les arrays imbriqués
            if (is_array($value)) {
                $redacted[$key] = $this->redactArray($value);
                continue;
            }

            // Valeur normale
            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * Vérifier si une clé est sensible
     *
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si une valeur contient un pattern sensible
     *
     * @param string $value
     * @return bool
     */
    private function isSensitiveValue(string $value): bool
    {
        foreach (self::SENSITIVE_VALUE_PATTERNS as $pattern) {
            if (str_starts_with($value, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Masquer une valeur sensible
     *
     * @param mixed $value
     * @return string
     */
    private function maskValue($value): string
    {
        if (!is_string($value)) {
            return '[REDACTED]';
        }

        $length = strlen($value);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        // Afficher les 4 premiers caractères et masquer le reste
        $visible = substr($value, 0, 4);
        $masked = str_repeat('*', min($length - 4, 20));

        return $visible . $masked . ($length > 24 ? '...' : '');
    }

    /**
     * Redacter un payload pour les logs (version plus stricte)
     *
     * @param array|string $payload
     * @return array
     */
    public function redactForLogs($payload): array
    {
        $redacted = $this->redact($payload);

        // Supprimer complètement certains champs pour les logs
        $fieldsToRemove = ['headers', 'signature', 'raw_signature'];
        
        foreach ($fieldsToRemove as $field) {
            if (isset($redacted[$field])) {
                unset($redacted[$field]);
            }
        }

        return $redacted;
    }
}




