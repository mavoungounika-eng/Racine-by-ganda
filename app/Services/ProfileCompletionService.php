<?php

namespace App\Services;

use App\Models\CreatorProfile;
use Illuminate\Support\Collection;

class ProfileCompletionService
{
    /**
     * Calculer le score de complétion du profil créateur.
     *
     * @param CreatorProfile $profile
     * @return array
     */
    public function calculateCompletionScore(CreatorProfile $profile): array
    {
        $steps = $this->getCompletionSteps($profile);
        
        $completedSteps = $steps->filter(fn($step) => $step['completed'])->count();
        $totalSteps = $steps->count();
        $percentage = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;
        
        return [
            'percentage' => round($percentage, 2),
            'completed_count' => $completedSteps,
            'total_count' => $totalSteps,
            'steps' => $steps->toArray(),
            'level' => $this->getCompletionLevel($percentage),
            'alerts' => $this->generateAlerts($steps, $percentage),
        ];
    }

    /**
     * Obtenir les étapes de complétion avec leur statut.
     *
     * @param CreatorProfile $profile
     * @return Collection
     */
    protected function getCompletionSteps(CreatorProfile $profile): Collection
    {
        return collect([
            // PROFIL BOUTIQUE (40 points)
            [
                'id' => 'brand_name',
                'category' => 'boutique',
                'title' => 'Nom de la boutique',
                'description' => 'Donnez un nom à votre boutique',
                'points' => 10,
                'completed' => !empty($profile->brand_name),
                'action' => route('creator.profile.show') . '#boutique',
                'priority' => 'high',
            ],
            [
                'id' => 'bio',
                'category' => 'boutique',
                'title' => 'Description de la boutique',
                'description' => 'Présentez votre univers créatif',
                'points' => 10,
                'completed' => !empty($profile->bio) && strlen($profile->bio) >= 50,
                'action' => route('creator.profile.show') . '#boutique',
                'priority' => 'high',
            ],
            [
                'id' => 'logo',
                'category' => 'boutique',
                'title' => 'Logo de la boutique',
                'description' => 'Ajoutez votre logo',
                'points' => 10,
                'completed' => !empty($profile->logo_path),
                'action' => route('creator.profile.show') . '#boutique',
                'priority' => 'medium',
            ],
            [
                'id' => 'banner',
                'category' => 'boutique',
                'title' => 'Bannière de la boutique',
                'description' => 'Personnalisez votre bannière',
                'points' => 10,
                'completed' => !empty($profile->banner_path),
                'action' => route('creator.profile.show') . '#boutique',
                'priority' => 'medium',
            ],
            
            // IDENTITÉ VENDEUR (20 points)
            [
                'id' => 'avatar',
                'category' => 'identite',
                'title' => 'Photo personnelle',
                'description' => 'Ajoutez votre photo de profil',
                'points' => 10,
                'completed' => !empty($profile->avatar_path),
                'action' => route('creator.profile.show') . '#identite',
                'priority' => 'high',
            ],
            [
                'id' => 'creator_title',
                'category' => 'identite',
                'title' => 'Titre/Fonction',
                'description' => 'Ex: Artisan maroquinier',
                'points' => 10,
                'completed' => !empty($profile->creator_title),
                'action' => route('creator.profile.show') . '#identite',
                'priority' => 'low',
            ],
            
            // RÉSEAUX SOCIAUX (10 points)
            [
                'id' => 'social',
                'category' => 'social',
                'title' => 'Réseaux sociaux',
                'description' => 'Ajoutez au moins un réseau social',
                'points' => 10,
                'completed' => !empty($profile->website) || 
                              !empty($profile->instagram_url) || 
                              !empty($profile->tiktok_url) ||
                              !empty($profile->facebook_url),
                'action' => route('creator.profile.show') . '#social',
                'priority' => 'low',
            ],
            
            // TECHNIQUE (30 points)
            [
                'id' => 'stripe',
                'category' => 'technique',
                'title' => 'Stripe Connect',
                'description' => 'Configurez vos paiements',
                'points' => 15,
                'completed' => $profile->stripeAccount && $profile->stripeAccount->payouts_enabled,
                'action' => route('creator.settings.payment'),
                'priority' => 'critical',
            ],
            [
                'id' => 'product',
                'category' => 'technique',
                'title' => 'Premier produit',
                'description' => 'Créez votre premier produit',
                'points' => 15,
                'completed' => $profile->products()->where('is_active', true)->exists(),
                'action' => route('creator.products.create'),
                'priority' => 'critical',
            ],
        ]);
    }

    /**
     * Déterminer le niveau de complétion.
     *
     * @param float $percentage
     * @return string
     */
    protected function getCompletionLevel(float $percentage): string
    {
        if ($percentage === 100) return 'complete';
        if ($percentage >= 76) return 'excellent';
        if ($percentage >= 51) return 'good';
        if ($percentage >= 26) return 'fair';
        return 'poor';
    }

    /**
     * Générer les alertes contextuelles.
     *
     * @param Collection $steps
     * @param float $percentage
     * @return array
     */
    protected function generateAlerts(Collection $steps, float $percentage): array
    {
        $alerts = [];
        
        // Alertes critiques (priorité haute)
        $criticalSteps = $steps->where('priority', 'critical')->where('completed', false);
        foreach ($criticalSteps as $step) {
            $alerts[] = [
                'type' => 'critical',
                'icon' => '🔴',
                'title' => 'Action requise',
                'message' => $step['title'] . ' : ' . $step['description'],
                'action' => $step['action'],
                'action_label' => 'Compléter maintenant',
            ];
        }
        
        // Alertes importantes (priorité haute)
        $highSteps = $steps->where('priority', 'high')->where('completed', false);
        foreach ($highSteps->take(2) as $step) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => '🟠',
                'title' => 'Recommandé',
                'message' => $step['title'] . ' : ' . $step['description'],
                'action' => $step['action'],
                'action_label' => 'Ajouter',
            ];
        }
        
        // Message de félicitations
        if ($percentage >= 76 && $percentage < 100) {
            $alerts[] = [
                'type' => 'success',
                'icon' => '🟢',
                'title' => 'Excellent travail !',
                'message' => 'Votre profil est presque complet. Plus que ' . (100 - $percentage) . '% !',
                'action' => null,
                'action_label' => null,
            ];
        }
        
        // Badge de complétion
        if ($percentage === 100) {
            $alerts[] = [
                'type' => 'complete',
                'icon' => '✅',
                'title' => 'Profil vérifié !',
                'message' => 'Félicitations ! Votre profil est complet à 100%.',
                'action' => null,
                'action_label' => null,
            ];
        }
        
        return $alerts;
    }

    /**
     * Obtenir les étapes incomplètes par catégorie.
     *
     * @param CreatorProfile $profile
     * @return array
     */
    public function getIncompleteStepsByCategory(CreatorProfile $profile): array
    {
        $steps = $this->getCompletionSteps($profile);
        
        return $steps
            ->where('completed', false)
            ->groupBy('category')
            ->map(fn($categorySteps) => $categorySteps->values())
            ->toArray();
    }
}
