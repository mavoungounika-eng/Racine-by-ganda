<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorVerifiedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('erp.auth.login');
        }
        
        $user = auth()->user();
        
        // Charger la relation roleRelation si nécessaire
        if (!$user->relationLoaded('roleRelation')) {
            $user->load('roleRelation');
        }
        
        // Vérifier si 2FA requis pour ce rôle (utiliser getRoleSlug() pour cohérence)
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'super_admin', 'moderator', 'moderateur'])) {
            $twoFactorService = app(\App\Services\TwoFactorService::class);
            
            if ($twoFactorService->isEnabled($user)) {
                // 2FA activé, vérifier si session vérifiée
                if (!session('2fa_verified')) {
                    return redirect()->route('erp.2fa.verify');
                }
            } else {
                // 2FA non activé mais requis pour admin/super_admin
                if (in_array($roleSlug, ['admin', 'super_admin'])) {
                    if (!session('2fa_setup_required')) {
                        session(['2fa_setup_required' => true]);
                        return redirect()->route('2fa.setup');
                    }
                }
            }
        }
        
        return $next($request);
    }
}
