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
            // Taux fixes de secours (fallback si API indisponible)
            $fallbackRates = [
                'XAF_EUR' => 0.001524,
                'XAF_USD' => 0.001634,
                'EUR_XAF' => 655.96,
                'USD_XAF' => 612.00,
            ];

            $apiKey = config('services.exchange_rate.api_key');
            $apiUrl = config('services.exchange_rate.url', 'https://v6.exchangerate-api.com/v6');

            if ($apiKey) {
                try {
                    $response = Http::timeout(5)->get("{$apiUrl}/{$apiKey}/pair/{$fromCurrency}/{$toCurrency}");

                    if ($response->successful()) {
                        $data = $response->json();
                        if (($data['result'] ?? '') === 'success' && isset($data['conversion_rate'])) {
                            return (float) $data['conversion_rate'];
                        }
                    }

                    Log::warning('Exchange rate API returned invalid response', [
                        'from'   => $fromCurrency,
                        'to'     => $toCurrency,
                        'status' => $response->status(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Exchange rate API unavailable, using fallback: ' . $e->getMessage());
                }
            }

            // Fallback : taux fixes
            $key = "{$fromCurrency}_{$toCurrency}";
            if (isset($fallbackRates[$key])) {
                return $fallbackRates[$key];
            }

            $reverseKey = "{$toCurrency}_{$fromCurrency}";
            if (isset($fallbackRates[$reverseKey])) {
                return 1.0 / $fallbackRates[$reverseKey];
            }
            return 1.0;
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

