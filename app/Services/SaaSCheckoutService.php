<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PaymentPreference;
use App\Models\CreatorProfile;
use App\Exceptions\OrderException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service de routage des paiements SaaS Pur.
 * 
 * Responsabilité : Déterminer quelle passerelle (RACINE ou CRÉATEUR) utiliser.
 */
class SaaSCheckoutService
{
    /**
     * Valide l'intégrité du panier pour le mode SaaS Pur.
     * Interdiction absolue des paniers mixtes (Brand + Creator).
     * 
     * @throws OrderException
     */
    public function validateCartIntegrity(Collection $cartItems): void
    {
        $types = $cartItems->map(fn($item) => $item->product->product_type)->unique();

        if ($types->count() > 1) {
            throw new OrderException(
                'Panier mixte non autorisé',
                422,
                "Pour garantir que votre paiement aille directement au bon destinataire, vous ne pouvez pas mélanger des produits RACINE avec des produits d'un créateur dans le même panier."
            );
        }

        // INVARIANT I6: Panier multi-créateurs INTERDIT
        // Un panier ne peut contenir des produits que d'un seul créateur
        $creatorUserIds = $cartItems
            ->filter(fn($item) => $item->product->product_type === 'marketplace')
            ->map(fn($item) => $item->product->user_id)
            ->unique();

        if ($creatorUserIds->count() > 1) {
            throw new OrderException(
                'Panier multi-créateurs non autorisé',
                422,
                "Un panier ne peut contenir des produits que d'un seul créateur. Veuillez commander séparément auprès de chaque créateur."
            );
        }

        // Si c'est un créateur, vérifier qu'il a une passerelle connectée
        $firstItem = $cartItems->first();
        if ($firstItem && $firstItem->product->product_type === 'marketplace') {
            $creatorId = $firstItem->product->user_id;
            $profile = CreatorProfile::where('user_id', $creatorId)->first();
            $prefs = $profile ? $profile->paymentPreference : null;

            if (!$prefs || !$prefs->isConnected()) {
                throw new OrderException(
                    'Paiement indisponible',
                    422,
                    "Ce créateur n'a pas encore configuré sa passerelle de paiement. La commande ne peut pas être finalisée."
                );
            }
        }
    }

    /**
     * Récupère les configurations de paiement pour une commande donnée.
     * 
     * @param int $creatorUserId ID de l'utilisateur (null si RACINE)
     * @return array [provider_keys]
     */
    public function getPaymentConfig(?int $creatorUserId = null): array
    {
        // 1. Si RACINE (Brand)
        if ($creatorUserId === null) {
            return [
                'type' => 'brand',
                'stripe_secret' => config('services.stripe.secret'),
                'stripe_public' => config('services.stripe.key'),
                'momo_provider' => config('services.monetbil.provider', 'monetbil'),
                'momo_api_key' => config('services.monetbil.api_key'),
            ];
        }

        // 2. Si CRÉATEUR (SaaS)
        $profile = CreatorProfile::where('user_id', $creatorUserId)->first();
        $prefs = $profile ? $profile->paymentPreference : null;

        if (!$prefs || !$prefs->isConnected()) {
            throw new \Exception("Tentative de paiement vers un créateur non connecté.");
        }

        return [
            'type' => 'creator',
            'creator_id' => $creatorUserId,
            'stripe_secret' => $prefs->stripe_secret_key,
            'stripe_public' => $prefs->stripe_publishable_key,
            'momo_provider' => $prefs->momo_provider,
            'momo_api_key' => $prefs->momo_api_key,
        ];
    }
}
