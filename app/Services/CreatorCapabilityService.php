<?php

namespace App\Services;

use App\Models\User;
use App\Models\CreatorPlan;
use App\Models\CreatorSubscription;
use App\Models\PlanCapability;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CreatorCapabilityService
 * 
 * Service central pour gérer les capabilities des créateurs.
 * 
 * Règles :
 * - Capabilities > Plans (pas de if plan == ...)
 * - Fallback automatique vers FREE si expiration
 * - Cache pour performance
 * - Aucune vue/controller ne lit la BD directement
 */
class CreatorCapabilityService
{
    /**
     * Durée du cache en minutes
     */
    protected int $cacheDuration = 60;

    /**
     * Charger l'abonnement actif d'un créateur.
     * 
     * @param User $creator
     * @return CreatorSubscription|null
     */
    public function getActiveSubscription(User $creator): ?CreatorSubscription
    {
        // ✅ OPTIMISATION N+1: Vérifier si la relation est déjà chargée
        // Évite requête DB si eager loaded dans le controller
        if ($creator->relationLoaded('creatorProfile') && 
            $creator->creatorProfile?->relationLoaded('subscription')) {
            $subscription = $creator->creatorProfile->subscription;
            
            // Vérifier que c'est bien un abonnement actif
            if ($subscription && 
                in_array($subscription->status, ['active', 'trialing']) &&
                ($subscription->ends_at === null || $subscription->ends_at > now())) {
                return $subscription;
            }
        }
        
        // Fallback vers cache si relation non chargée (compatibilité)
        $cacheKey = "creator_subscription_active_{$creator->id}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($creator) {
            // Récupérer via creator_id (si disponible) ou creator_profile_id (fallback)
            $subscription = CreatorSubscription::where(function ($query) use ($creator) {
                $query->where('creator_id', $creator->id)
                      ->orWhereHas('creatorProfile', function ($q) use ($creator) {
                          $q->where('user_id', $creator->id);
                      });
            })
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->with(['plan', 'plan.capabilities'])
            ->first();

            // Si pas d'abonnement actif, retourner null (sera géré par fallback)
            return $subscription;
        });
    }

    /**
     * Obtenir le plan actif d'un créateur (avec fallback FREE).
     * 
     * @param User $creator
     * @return CreatorPlan
     */
    public function getActivePlan(User $creator): CreatorPlan
    {
        $subscription = $this->getActiveSubscription($creator);

        // Si abonnement actif et valide, retourner son plan
        if ($subscription && $subscription->plan) {
            return $subscription->plan;
        }

        // Fallback : plan FREE
        return $this->getFreePlan();
    }

    /**
     * Obtenir le plan FREE.
     * 
     * @return CreatorPlan
     */
    protected function getFreePlan(): CreatorPlan
    {
        $cacheKey = 'creator_plan_free';

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            $plan = CreatorPlan::where('code', 'free')->first();
            
            if (!$plan) {
                Log::error('Plan FREE non trouvé dans la base de données');
                throw new \RuntimeException('Plan FREE non trouvé. Exécutez les seeders.');
            }

            return $plan;
        });
    }

    /**
     * Vérifier si un créateur a une capability (bool).
     * 
     * V2.2 : Prend en compte les add-ons en plus du plan.
     * 
     * @param User $creator
     * @param string $capabilityKey
     * @return bool
     */
    public function can(User $creator, string $capabilityKey): bool
    {
        // 1. Vérifier la capability du plan
        $planValue = $this->value($creator, $capabilityKey);
        
        if ($planValue) {
            // Si la valeur est un bool, retourner directement
            if (is_bool($planValue)) {
                return $planValue;
            }

            // Si c'est un array avec 'bool', utiliser ça
            if (is_array($planValue) && isset($planValue['bool'])) {
                return (bool) $planValue['bool'];
            }

            // Sinon, convertir en bool
            if ((bool) $planValue) {
                return true; // Activé par le plan
            }
        }

        // 2. V2.2 : Vérifier si un add-on active cette capability
        try {
            $addonService = app(\App\Services\CreatorAddonService::class);
            $addon = \App\Models\CreatorAddon::where('capability_key', $capabilityKey)
                ->where('is_active', true)
                ->first();

            if ($addon && $addonService->hasAddon($creator, $addon->code)) {
                // Capability activée par add-on
                // Si l'add-on a une valeur spécifique, l'utiliser
                if ($addon->capability_value) {
                    $addonValue = $addon->capability_value;
                    if (is_bool($addonValue)) {
                        return $addonValue;
                    }
                    if (is_array($addonValue) && isset($addonValue['bool'])) {
                        return (bool) $addonValue['bool'];
                    }
                    return (bool) $addonValue;
                }
                // Sinon, retourner true (add-on actif = capability activée)
                return true;
            }
        } catch (\Exception $e) {
            // Si le service add-on n'est pas disponible, continuer avec le plan uniquement
            Log::warning('Erreur lors de la vérification des add-ons', [
                'creator_id' => $creator->id,
                'capability_key' => $capabilityKey,
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Retourner false si ni plan ni add-on n'active la capability
        return false;
    }

    /**
     * Obtenir la valeur d'une capability.
     * 
     * @param User $creator
     * @param string $capabilityKey
     * @return mixed
     */
    public function value(User $creator, string $capabilityKey)
    {
        $cacheKey = "creator_capability_{$creator->id}_{$capabilityKey}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($creator, $capabilityKey) {
            $plan = $this->getActivePlan($creator);

            // Charger la capability depuis le plan
            $capability = PlanCapability::where('creator_plan_id', $plan->id)
                ->where('capability_key', $capabilityKey)
                ->first();

            if (!$capability) {
                // Si la capability n'existe pas, retourner false par défaut
                Log::warning("Capability '{$capabilityKey}' non trouvée pour le plan '{$plan->code}'");
                return false;
            }

            return $capability->getRawValue();
        });
    }

    /**
     * Obtenir toutes les capabilities d'un créateur.
     * 
     * @param User $creator
     * @return array
     */
    public function capabilities(User $creator): array
    {
        $cacheKey = "creator_capabilities_{$creator->id}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($creator) {
            $plan = $this->getActivePlan($creator);

            $capabilities = PlanCapability::where('creator_plan_id', $plan->id)
                ->get()
                ->mapWithKeys(function ($capability) {
                    return [$capability->capability_key => $capability->getRawValue()];
                })
                ->toArray();

            return $capabilities;
        });
    }

    /**
     * Invalider le cache pour un créateur.
     * 
     * @param User $creator
     * @return void
     */
    public function clearCache(User $creator): void
    {
        $keys = [
            "creator_subscription_active_{$creator->id}",
            "creator_capabilities_{$creator->id}",
        ];

        // Invalider toutes les capabilities individuelles
        $plan = $this->getActivePlan($creator);
        $capabilityKeys = PlanCapability::where('creator_plan_id', $plan->id)
            ->pluck('capability_key')
            ->map(fn($key) => "creator_capability_{$creator->id}_{$key}")
            ->toArray();

        $keys = array_merge($keys, $capabilityKeys);

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Vérifier si un créateur peut ajouter un produit (avec limite).
     * 
     * @param User $creator
     * @return bool
     */
    public function canAddProduct(User $creator): bool
    {
        if (!$this->can($creator, 'can_add_products')) {
            return false;
        }

        $maxProducts = $this->value($creator, 'max_products');

        // -1 = illimité
        if ($maxProducts === -1 || (is_array($maxProducts) && isset($maxProducts['int']) && $maxProducts['int'] === -1)) {
            return true;
        }

        // Vérifier le nombre actuel de produits
        $currentCount = $creator->creatorProfile?->products()->count() ?? 0;
        $max = is_array($maxProducts) && isset($maxProducts['int']) 
            ? $maxProducts['int'] 
            : (int) $maxProducts;

        return $currentCount < $max;
    }

    /**
     * Obtenir le layout du dashboard pour un créateur.
     * 
     * @param User $creator
     * @return string
     */
    public function getDashboardLayout(User $creator): string
    {
        $layout = $this->value($creator, 'dashboard_layout');

        if (is_array($layout) && isset($layout['string'])) {
            return $layout['string'];
        }

        if (is_string($layout)) {
            return $layout;
        }

        // Fallback
        return 'basic';
    }
}

