<?php

if (!function_exists('currency')) {
  function currency(
    float $amount,
    string $from = 'XAF',
    ?string $to = null
  ): string {
    $to = $to ?? session('currency',
      config('currency.default', 'XAF'));
    $service = app(
      \App\Services\Currency\CurrencyService::class);
    return $service->format(
      $service->convert($amount, $from, $to), $to);
  }
}

if (!function_exists('currency_raw')) {
  function currency_raw(
    float $amount,
    string $from = 'XAF',
    ?string $to = null
  ): float {
    $to = $to ?? session('currency',
      config('currency.default', 'XAF'));
    return app(
      \App\Services\Currency\CurrencyService::class)
      ->convert($amount, $from, $to);
  }
}

if (!function_exists('current_currency')) {
  function current_currency(): string {
    return session('currency',
      config('currency.default', 'XAF'));
  }
}

if (!function_exists('current_currency_symbol')) {
  function current_currency_symbol(): string {
    $currency = current_currency();
    return config("currency.symbols.{$currency}", $currency);
  }
}

if (!function_exists('csp_nonce')) {
    /**
     * Retourne le nonce CSP de la requête courante.
     * Cherche dans l'ordre : config runtime → request attributes → vide.
     */
    function csp_nonce(): string {
        return config('csp.nonce')
            ?? request()->attributes->get('csp_nonce')
            ?? '';
    }
}
if (!function_exists('format_price')) {
    function format_price(float $amount, ?string $fromCurrency = null): string {
        $service = app(\App\Services\Currency\CurrencyService::class);
        $from = $fromCurrency ?? config('currency.reference', 'XAF');
        $to   = current_currency();
        $converted = $service->convert($amount, $from, $to);
        return $service->format($converted, $to);
    }
}

if (!function_exists('convert_price')) {
    function convert_price(float $amount, ?string $fromCurrency = null): float {
        $service = app(\App\Services\Currency\CurrencyService::class);
        $from = $fromCurrency ?? config('currency.reference', 'XAF');
        $to   = current_currency();
        return $service->convert($amount, $from, $to);
    }
}
