<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureCapability Middleware
 * 
 * Vérifie qu'un créateur a une capability spécifique.
 * 
 * Usage: ->middleware('capability:can_manage_collections')
 * 
 * Si la capability n'est pas disponible, redirige vers une page d'upgrade.
 */
class EnsureCapability
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $capabilityKey
     */
    public function handle(Request $request, Closure $next, string $capabilityKey): Response
    {
        $user = $request->user();

        // Si pas authentifié, rediriger vers login
        if (!$user) {
            return redirect()->route('creator.login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        // Si pas créateur, refuser l'accès
        if (!$user->isCreator()) {
            abort(403, 'Accès réservé aux créateurs.');
        }

        // Vérifier la capability
        if (!$user->hasCapability($capabilityKey)) {
            // Blocage soft : rediriger vers page upgrade avec message UX clair
            return redirect()->route('creator.subscription.upgrade')
                ->with('upgrade_message', $this->getUpgradeMessage($capabilityKey));
        }

        return $next($request);
    }

    /**
     * Obtenir le message d'upgrade selon la capability.
     * 
     * @param string $capabilityKey
     * @return string
     */
    protected function getUpgradeMessage(string $capabilityKey): string
    {
        $messages = [
            'can_manage_collections' => 'La gestion des collections est disponible avec le plan Officiel ou Premium.',
            'can_view_advanced_stats' => 'Les statistiques avancées sont disponibles avec le plan Officiel ou Premium.',
            'can_view_analytics' => 'Les analytics sont disponibles avec le plan Officiel ou Premium.',
            'can_export_data' => 'L\'export de données est disponible avec le plan Officiel ou Premium.',
            'can_use_api' => 'L\'accès API est disponible avec le plan Premium uniquement.',
        ];

        return $messages[$capabilityKey] ?? 'Cette fonctionnalité nécessite un abonnement supérieur.';
    }
}
