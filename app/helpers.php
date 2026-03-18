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
