<?php
namespace App\Services;

/**
 * Calcule les frais de livraison selon la zone détectée.
 */
class ShippingService
{
    /**
     * Retourne la clé de zone pour un code pays ISO.
     * Si country est null, utilise la devise de session comme fallback.
     */
    public function detectZone(?string $countryCode): string
    {
        if ($countryCode) {
            $code = strtoupper(trim($countryCode));
            foreach (config('shipping.zones') as $key => $zone) {
                if (in_array($code, $zone['countries'], true)) {
                    return $key;
                }
            }
            // Pays connu mais pas dans une zone définie → international
            return 'international';
        }

        // Fallback devise de session
        $currency = session('currency', config('currency.default', 'XAF'));
        return config("shipping.currency_zone_fallback.{$currency}", config('shipping.default_zone', 'local'));
    }

    /**
     * Retourne le coût de livraison en XAF pour une zone et un sous-total donnés.
     * Retourne 0 si le sous-total dépasse le seuil de gratuité.
     */
    public function cost(string $zone, float $subtotalXAF): float
    {
        $zoneConfig = config("shipping.zones.{$zone}");
        if (!$zoneConfig) {
            $zoneConfig = config('shipping.zones.international');
        }

        if ($subtotalXAF >= $zoneConfig['free_above']) {
            return 0.0;
        }

        return (float) $zoneConfig['cost'];
    }

    /**
     * Retourne la config complète d'une zone.
     */
    public function zoneConfig(string $zone): array
    {
        return config("shipping.zones.{$zone}", config('shipping.zones.international'));
    }

    /**
     * Retourne toutes les zones (pour la vue /livraison).
     */
    public function allZones(): array
    {
        return config('shipping.zones', []);
    }
}
