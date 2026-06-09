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

if (!function_exists('responsive_srcset')) {
    /**
     * Generate srcset attribute for responsive images.
     *
     * Given an image path like "storage/hero/hero-01.jpeg",
     * returns srcset with 400w, 800w, 1200w variants.
     *
     * Example output:
     * "http://localhost/storage/hero/hero-01_400w.jpeg 400w,
     *  http://localhost/storage/hero/hero-01_800w.jpeg 800w,
     *  http://localhost/storage/hero/hero-01_1200w.jpeg 1200w"
     *
     * @param string $src Original image path (can be asset() URL or relative path)
     * @return string Srcset attribute value
     */
    function responsive_srcset(string $src): string {
        // Extract path from full URL if needed
        $path = str_replace(url('/'), '', $src);
        $path = ltrim($path, '/');

        // Parse path info
        $pathInfo = pathinfo($path);
        $directory = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? '';
        $extension = $pathInfo['extension'] ?? '';

        $srcsetParts = [];

        foreach ([400, 800, 1200] as $width) {
            $variantFilename = "{$filename}_{$width}w.{$extension}";
            $variantPath = $directory ? "{$directory}/{$variantFilename}" : $variantFilename;

            // Check if variant exists
            $fullPath = public_path($variantPath);

            if (file_exists($fullPath)) {
                $srcsetParts[] = asset($variantPath) . " {$width}w";
            }
        }

        // If no variants exist, return original src as fallback
        if (empty($srcsetParts)) {
            return $src;
        }

        return implode(', ', $srcsetParts);
    }
}
