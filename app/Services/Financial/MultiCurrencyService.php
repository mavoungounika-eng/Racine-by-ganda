<?php

namespace App\Services\Financial;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion multi-devises et multi-pays
 * 
 * Phase 6.5 - Préparation Scalabilité
 */
class MultiCurrencyService
{
    /**
     * Taux de change supportés
     */
    protected array $supportedCurrencies = [
        'XAF' => 'Franc CFA',
        'EUR' => 'Euro',
        'USD' => 'Dollar US',
    ];

    /**
     * Pays supportés avec leur devise par défaut
     */
    protected array $supportedCountries = [
        'CG' => ['currency' => 'XAF', 'name' => 'République du Congo'],
        'FR' => ['currency' => 'EUR', 'name' => 'France'],
        'US' => ['currency' => 'USD', 'name' => 'États-Unis'],
    ];

    /**
     * Convertir un montant d'une devise à une autre
     * 
     * @param float $amount Montant à convertir
     * @param string $fromCurrency Devise source (ex: 'XAF')
     * @param string $toCurrency Devise cible (ex: 'EUR')
     * @return float Montant converti
     */
    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return round($amount * $rate, 2);
    }

    /**
     * Obtenir le taux de change entre deux devises
     * 
     * @param string $fromCurrency Devise source
     * @param string $toCurrency Devise cible
     * @return float Taux de change
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($fromCurrency, $toCurrency) {
            // TODO: Intégrer une API de taux de change (ex: exchangerate-api.com, fixer.io)
            // Pour l'instant, utiliser des taux fixes (à remplacer par une vraie API)
            
            $rates = [
                'XAF_EUR' => 0.0015, // 1 XAF = 0.0015 EUR (approximatif)
                'XAF_USD' => 0.0016, // 1 XAF = 0.0016 USD (approximatif)
                'EUR_XAF' => 655.96, // 1 EUR = 655.96 XAF
                'USD_XAF' => 600.00, // 1 USD = 600 XAF (approximatif)
            ];

            $key = "{$fromCurrency}_{$toCurrency}";
            
            if (isset($rates[$key])) {
                return $rates[$key];
            }

            // Si le taux inverse existe, l'inverser
            $reverseKey = "{$toCurrency}_{$fromCurrency}";
            if (isset($rates[$reverseKey])) {
                return 1 / $rates[$reverseKey];
            }

            Log::warning("Taux de change non trouvé: {$fromCurrency} → {$toCurrency}");
            return 1.0; // Fallback
        });
    }

    /**
     * Obtenir la devise par défaut d'un pays
     * 
     * @param string $countryCode Code pays ISO (ex: 'CG', 'FR')
     * @return string|null Code devise (ex: 'XAF', 'EUR')
     */
    public function getCurrencyForCountry(string $countryCode): ?string
    {
        return $this->supportedCountries[$countryCode]['currency'] ?? null;
    }

    /**
     * Formater un montant selon la devise
     * 
     * @param float $amount Montant
     * @param string $currency Code devise
     * @return string Montant formaté
     */
    public function formatAmount(float $amount, string $currency): string
    {
        $formatters = [
            'XAF' => function ($amount) {
                return number_format($amount, 0, ',', ' ') . ' XAF';
            },
            'EUR' => function ($amount) {
                return number_format($amount, 2, ',', ' ') . ' €';
            },
            'USD' => function ($amount) {
                return '$' . number_format($amount, 2, '.', ',');
            },
        ];

        $formatter = $formatters[$currency] ?? $formatters['XAF'];
        return $formatter($amount);
    }

    /**
     * Obtenir les devises supportées
     * 
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * Obtenir les pays supportés
     * 
     * @return array
     */
    public function getSupportedCountries(): array
    {
        return $this->supportedCountries;
    }
}

