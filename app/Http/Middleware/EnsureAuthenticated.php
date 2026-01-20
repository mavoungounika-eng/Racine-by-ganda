<?php

namespace App\Http\Middleware;

use App\Services\Auth\UserContextResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unified Authentication & Authorization Middleware
 * 
 * SINGLE SOURCE OF TRUTH for all protected routes.
 * 
 * Responsibilities:
 * - Verify user is authenticated
 * - Load UserContext from session (ZERO DB queries)
 * - Validate auth_version (prevent privilege escalation)
 * - Check role authorization
 * 
 * Usage:
 * Route::middleware(['auth', 'ensure:admin,super_admin'])->group(...)
 * Route::middleware(['auth', 'ensure:creator'])->group(...)
 * Route::middleware(['auth', 'ensure'])->group(...) // any authenticated user
 */
class EnsureAuthenticated
{
    public function __construct(
        private UserContextResolver $contextResolver
    ) {}

    /**
     * Handle an incoming request
     * 
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles Allowed roles (empty = any authenticated user)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Step 1: Check authentication
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();

        // Refresh user from DB to get latest auth_version
        $user->refresh();

        // Step 2: Get UserContext from session (ZERO DB query)
        $context = $this->contextResolver->getFromSession();

        if (!$context) {
            // No context in session - invalid state, logout and redirect
            Log::warning('EnsureAuthenticated: Missing UserContext in session', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Votre session a expiré. Veuillez vous reconnecter.');
        }

        // Step 3: Validate auth_version (prevent privilege escalation)
        if (!$this->contextResolver->validateSession($user, $context)) {
            // auth_version mismatch - user context changed in DB
            Log::warning('EnsureAuthenticated: auth_version mismatch (privilege escalation prevented)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'session_auth_version' => $context->authVersion,
                'db_auth_version' => $user->auth_version,
                'session_role' => $context->role,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Votre session a été invalidée suite à une modification de votre compte. Veuillez vous reconnecter.');
        }

        // Step 4: Check role authorization (if roles specified)
        if (!empty($roles)) {
            if (!in_array($context->role, $roles, true)) {
                // User's role not in allowed roles
                Log::warning('EnsureAuthenticated: Unauthorized access attempt', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'user_role' => $context->role,
                    'required_roles' => $roles,
                    'url' => $request->url(),
                ]);

                abort(403, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
            }
        }

        // Step 5: Authorization successful - continue
        return $next($request);
    }
}
