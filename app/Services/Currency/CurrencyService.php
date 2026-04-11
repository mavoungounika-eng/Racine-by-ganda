<?php

namespace App\Services\Currency;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

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

    public function toStripeCents(float $amountXaf): int
    {
        // XAF → EUR → centimes Stripe
        $eur = $this->convert($amountXaf, 'XAF', 'EUR');
        return (int) round($eur * 100);
    }
}
