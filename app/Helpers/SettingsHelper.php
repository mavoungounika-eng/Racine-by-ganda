<?php

if (!function_exists('setting')) {
    /**
     * Récupérer un paramètre du site depuis le cache
     *
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return \Illuminate\Support\Facades\Cache::get("settings.{$key}", $default);
    }
}

if (!function_exists('settings')) {
    /**
     * Récupérer tous les paramètres du site
     *
     * @return array
     */
    function settings(): array
    {
        return [
            'site_name' => setting('site_name', config('app.name')),
            'site_email' => setting('site_email', config('mail.from.address')),
            'site_phone' => setting('site_phone', ''),
            'site_address' => setting('site_address', ''),
            'social_facebook' => setting('social_facebook', ''),
            'social_instagram' => setting('social_instagram', ''),
            'social_twitter' => setting('social_twitter', ''),
            'social_whatsapp' => setting('social_whatsapp', ''),
            'commission_rate' => setting('commission_rate', 15.00),
            'shipping_fee' => setting('shipping_fee', 2000),
            'currency' => setting('currency', 'FCFA'),
            'low_stock_threshold' => setting('low_stock_threshold', 10),
            'stripe_mode' => setting('stripe_mode', 'test'),
            'payments_enabled' => setting('payments_enabled', true),
            'registrations_enabled' => setting('registrations_enabled', true),
            'maintenance_message' => setting('maintenance_message', ''),
        ];
    }
}
