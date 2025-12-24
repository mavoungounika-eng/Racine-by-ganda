<?php

namespace App\Services;

use App\Models\CreatorAddon;
use App\Models\CreatorBundle;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * CreatorBundleService
 * 
 * Service pour gérer les bundles (packs avec plan + add-ons).
 * 
 * RÈGLE : Un bundle = plan de base + add-ons activés.
 */
class CreatorBundleService
{
    protected CreatorAddonService $addonService;

    public function __construct(CreatorAddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Activer un bundle pour un créateur.
     * 
     * RÈGLE : Un bundle = plan de base + add-ons activés.
     */
    public function activateBundle(User $creator, CreatorBundle $bundle): CreatorSubscription
    {
        // 1. Activer le plan de base
        $subscription = CreatorSubscription::updateOrCreate(
            ['creator_id' => $creator->id],
            [
                'creator_profile_id' => $creator->creatorProfile->id ?? null,
                'creator_plan_id' => $bundle->base_plan_id,
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
                'metadata' => array_merge(
                    $creator->activeSubscription()?->metadata ?? [],
                    [
                        'bundle_id' => $bundle->id,
                        'bundle_code' => $bundle->code,
                        'bundle_activated_at' => now()->toIso8601String(),
                    ]
                ),
            ]
        );

        // 2. Activer les add-ons inclus
        $addonIds = $bundle->included_addon_ids ?? [];
        
        foreach ($addonIds as $addonId) {
            $addon = CreatorAddon::find($addonId);
            if ($addon && $addon->is_active) {
                try {
                    $this->addonService->activateAddon($creator, $addon);
                } catch (\Exception $e) {
                    Log::warning('Erreur lors de l\'activation d\'un add-on du bundle', [
                        'creator_id' => $creator->id,
                        'bundle_id' => $bundle->id,
                        'addon_id' => $addonId,
                        'error' => $e->getMessage(),
                    ]);
                    // Continuer avec les autres add-ons
                }
            }
        }

        // 3. Invalider le cache
        app(CreatorCapabilityService::class)->clearCache($creator);

        // 4. Tracker l'événement
        app(SubscriptionAnalyticsService::class)->trackEvent(
            $creator->id,
            'created',
            null,
            $bundle->base_plan_id,
            $bundle->price,
            ['bundle_id' => $bundle->id, 'bundle_code' => $bundle->code]
        );

        Log::info('Bundle activé pour créateur', [
            'creator_id' => $creator->id,
            'bundle_id' => $bundle->id,
            'bundle_code' => $bundle->code,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }
}



