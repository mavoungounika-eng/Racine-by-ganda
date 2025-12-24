<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;

/**
 * Service pour vérifier le statut de configuration des providers
 * 
 * Ne vérifie QUE la présence des variables d'environnement, jamais les valeurs.
 * Cache 60s pour éviter surcoût.
 */
class ProviderConfigStatusService
{
    /**
     * Vérifier le statut de configuration d'un provider
     *
     * @param string $providerCode (stripe, monetbil)
     * @return array ['status' => 'ok'|'ko', 'message' => string, 'missing_keys' => array]
     */
    public function checkConfigStatus(string $providerCode): array
    {
        $cacheKey = "payment_provider_config_status_{$providerCode}";
        
        return Cache::remember($cacheKey, 60, function () use ($providerCode) {
            return $this->doCheckConfigStatus($providerCode);
        });
    }

    /**
     * Vérification réelle (sans cache)
     *
     * @param string $providerCode
     * @return array
     */
    private function doCheckConfigStatus(string $providerCode): array
    {
        $requiredKeys = $this->getRequiredKeys($providerCode);
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            $value = env($key);
            // Vérifier présence (même vide, si la clé existe dans .env c'est OK)
            // Mais on vérifie surtout que la config Laravel a la clé
            $configValue = config("services.{$providerCode}." . str_replace("{$providerCode}_", '', strtolower($key)));
            
            if (empty($value) && empty($configValue)) {
                $missingKeys[] = $key;
            }
        }

        if (empty($missingKeys)) {
            return [
                'status' => 'ok',
                'message' => 'Configuration complète',
                'missing_keys' => [],
            ];
        }

        return [
            'status' => 'ko',
            'message' => 'Configuration incomplète : ' . implode(', ', $missingKeys),
            'missing_keys' => $missingKeys,
        ];
    }

    /**
     * Obtenir les clés requises pour un provider
     *
     * @param string $providerCode
     * @return array
     */
    private function getRequiredKeys(string $providerCode): array
    {
        return match ($providerCode) {
            'stripe' => [
                'STRIPE_PUBLIC_KEY',
                'STRIPE_SECRET_KEY',
                'STRIPE_WEBHOOK_SECRET',
            ],
            'monetbil' => [
                'MONETBIL_SERVICE_KEY',
                'MONETBIL_SERVICE_SECRET',
                'MONETBIL_NOTIFY_URL',
                'MONETBIL_RETURN_URL',
            ],
            default => [],
        };
    }

    /**
     * Vider le cache pour un provider
     *
     * @param string $providerCode
     * @return void
     */
    public function clearCache(string $providerCode): void
    {
        Cache::forget("payment_provider_config_status_{$providerCode}");
    }
}




