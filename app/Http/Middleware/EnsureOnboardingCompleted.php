<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    private const SKIP_ROLES = ['admin', 'super_admin', 'staff'];

    private const SKIP_ROUTES = [
        'onboarding.*',
        'logout',
        'login',
        '2fa.*',
        'terms.*',
        'api.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->onboarding_completed) {
            return $next($request);
        }

        // Admins/staff : pas d'onboarding
        if (in_array($user->role, self::SKIP_ROLES)) {
            return $next($request);
        }

        // Éviter boucle redirect sur les routes onboarding
        foreach (self::SKIP_ROUTES as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        $isCreator = in_array($user->role, ['createur', 'creator']);

        return redirect()->route($isCreator ? 'onboarding.creator' : 'onboarding.client');
    }
}
