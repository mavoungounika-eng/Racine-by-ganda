<?php

namespace App\Services;

use App\Models\CreatorAddon;
use App\Models\CreatorSubscriptionAddon;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * CreatorAddonService
 * 
 * Service pour gérer les add-ons des créateurs.
 * 
 * RÈGLE : Tout add-on = une capability.
 */
class CreatorAddonService
{
    /**
     * Activer un add-on pour un créateur.
     * 
     * RÈGLE : Tout add-on = une capability.
     */
    public function activateAddon(User $creator, CreatorAddon $addon): CreatorSubscriptionAddon
    {
        $subscription = $creator->activeSubscription();
        
        if (!$subscription) {
            throw new \RuntimeException('Aucun abonnement actif.');
        }

        // Vérifier si l'add-on est déjà actif
        $existing = CreatorSubscriptionAddon::where('creator_subscription_id', $subscription->id)
            ->where('creator_addon_id', $addon->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return $existing; // Déjà actif
        }

        // Calculer la date d'expiration selon le cycle de facturation
        $expiresAt = null;
        if ($addon->billing_cycle === 'monthly') {
            $expiresAt = now()->addMonth();
        } elseif ($addon->billing_cycle === 'annually') {
            $expiresAt = now()->addYear();
        }
        // one_time = pas d'expiration

        // Créer l'add-on
        $subscriptionAddon = CreatorSubscriptionAddon::create([
            'creator_subscription_id' => $subscription->id,
            'creator_addon_id' => $addon->id,
            'activated_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Invalider le cache pour activer la capability
        app(CreatorCapabilityService::class)->clearCache($creator);

        Log::info('Add-on activé pour créateur', [
            'creator_id' => $creator->id,
            'addon_id' => $addon->id,
            'addon_code' => $addon->code,
            'subscription_addon_id' => $subscriptionAddon->id,
        ]);

        return $subscriptionAddon;
    }

    /**
     * Vérifier si un créateur a un add-on actif.
     */
    public function hasAddon(User $creator, string $addonCode): bool
    {
        $subscription = $creator->activeSubscription();
        
        if (!$subscription) {
            return false;
        }

        return CreatorSubscriptionAddon::where('creator_subscription_id', $subscription->id)
            ->whereHas('addon', function ($query) use ($addonCode) {
                $query->where('code', $addonCode);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Obtenir tous les add-ons actifs d'un créateur.
     */
    public function getActiveAddons(User $creator): \Illuminate\Database\Eloquent\Collection
    {
        $subscription = $creator->activeSubscription();
        
        if (!$subscription) {
            return collect();
        }

        return CreatorSubscriptionAddon::where('creator_subscription_id', $subscription->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with('addon')
            ->get()
            ->map(fn($sa) => $sa->addon);
    }
}



