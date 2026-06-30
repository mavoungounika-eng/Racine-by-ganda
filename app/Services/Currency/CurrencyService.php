<?php

namespace App\Services\Currency;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) return $amount;

        $rates = config('currency.rates');

        if (!isset($rates[$from][$to])) {
            throw new \InvalidArgumentException("Taux inconnu : {$from} → {$to}");
        }

        $converted = $amount * $rates[$from][$to];
        $decimals = config("currency.decimals.{$to}", 2);

        return round($converted, $decimals);
    }

    public function format(float $amount, string $currency, bool $withSymbol = true): string
    {
        $decimals = config("currency.decimals.{$currency}", 2);
        $symbol = config("currency.symbols.{$currency}", $currency);

        if (in_array($currency, ['XAF', 'XOF'])) {
            // Format CFA : "15 000 FCFA"
            $formatted = number_format($amount, 0, ',', ' ');
            return $withSymbol ? "{$formatted} {$symbol}" : $formatted;
        }

        // Format EUR : "22,89 €"
        $formatted = number_format($amount, 2, ',', ' ');
        return $withSymbol ? "{$formatted} {$symbol}" : $formatted;
    }

    public function getSymbol(string $currency): string
    {
        return config("currency.symbols.{$currency}", $currency);
    }

    public function getSupportedCurrencies(): array
    {
        return config('currency.supported', ['XAF']);
    }

    public function getRate(string $from, string $to): float
    {
        $cacheKey = "currency_rate:{$from}:{$to}";
        return Cache::remember($cacheKey, 86400, function () use ($from, $to) {
            return config("currency.rates.{$from}.{$to}", 1.0);
        });
    }

    public function getUserCurrency(?User $user): string
    {
        if ($user && $user->preferred_currency) {
            return $user->preferred_currency;
        }
        return session('currency', config('currency.default', 'XAF'));
    }

    public function setSessionCurrency(string $currency): void
    {
        $supported = config('currency.supported', ['XAF']);
        if (!in_array($currency, $supported)) {
            throw new \InvalidArgumentException("Devise non supportée : {$currency}");
        }
        session(['currency' => $currency]);
    }

    public function detectCurrencyFromPhone(string $phone): string
    {
        $prefixes = config('currency.phone_prefixes', []);
        foreach ($prefixes as $prefix => $currency) {
            if (str_starts_with($phone, $prefix)) {
                return $currency;
            }
        }
        return config('currency.default', 'XAF');
    }

    /**
     * Convertir un montant en utilisant l'Exchange Rate API (exchangerate-api.com).
     *
     * Tente d'abord la clé configurée (EXCHANGE_RATE_API_KEY), sinon
     * retombe sur les taux statiques de config/currency.php.
     * Résultat mis en cache 1 heure pour limiter les appels API.
     *
     * @param float  $amount Montant à convertir
     * @param string $from   Devise source (ex: 'EUR')
     * @param string $to     Devise cible (ex: 'XAF')
     * @return float Montant converti, arrondi selon la devise cible
     */
    public function convertViaApi(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $apiKey = config('services.exchange_rate.api_key');

        if (empty($apiKey)) {
            // Pas de clé API → taux statiques
            return $this->convert($amount, $from, $to);
        }

        $cacheKey = "exchange_rate:{$from}:{$to}";

        $rate = Cache::remember($cacheKey, 3600, function () use ($apiKey, $from, $to) {
            $baseUrl = rtrim(config('services.exchange_rate.url', 'https://v6.exchangerate-api.com/v6'), '/');
            $url     = "{$baseUrl}/{$apiKey}/pair/{$from}/{$to}";

            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if (($data['result'] ?? '') === 'success' && isset($data['conversion_rate'])) {
                        return (float) $data['conversion_rate'];
                    }

                    Log::warning('Exchange Rate API unexpected response', [
                        'from' => $from,
                        'to'   => $to,
                        'body' => $response->body(),
                    ]);
                } else {
                    Log::error('Exchange Rate API HTTP error', [
                        'status' => $response->status(),
                        'from'   => $from,
                        'to'     => $to,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exchange Rate API exception', [
                    'from'  => $from,
                    'to'    => $to,
                    'error' => $e->getMessage(),
                ]);
            }

            // Fallback : taux statique depuis config
            return config("currency.rates.{$from}.{$to}", null);
        });

        if ($rate === null) {
            throw new \InvalidArgumentException("Taux inconnu : {$from} → {$to}");
        }

        $decimals = config("currency.decimals.{$to}", 2);
        return round($amount * (float) $rate, $decimals);
    }

    public function toStripeCents(float $amountXaf): int
    {
        // XAF → EUR → centimes Stripe
        $eur = $this->convertViaApi($amountXaf, 'XAF', 'EUR');
        return (int) round($eur * 100);
    }
}
