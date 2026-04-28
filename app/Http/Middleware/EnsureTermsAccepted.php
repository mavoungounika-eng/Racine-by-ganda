<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    // Roles exemptés (staff, admin n'ont pas besoin d'accepter les CGU client)
    private const EXEMPT_ROLES = ['admin', 'super_admin', 'staff'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Exempter les rôles internes
        $role = $user->roleRelation?->slug ?? $user->role ?? 'client';
        if (in_array($role, self::EXEMPT_ROLES)) {
            return $next($request);
        }

        // Exempter les routes d'acceptation et logout
        if ($request->routeIs('terms.accept', 'terms.accept.post', 'logout')) {
            return $next($request);
        }

        // Vérifier si les CGU ont été acceptées
        if (is_null($user->terms_accepted_at)) {
            return redirect()->route('terms.accept');
        }

        return $next($request);
    }
}
