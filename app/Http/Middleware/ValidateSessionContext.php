<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate Session Context Middleware
 * 
 * CRITICAL SECURITY MIDDLEWARE
 * 
 * Validates that the frozen UserContext in session is still valid by checking:
 * - auth_version match (prevents privilege escalation)
 * - TTL not expired
 * - User not suspended/deleted
 * 
 * MUST be registered in Kernel.php AFTER 'auth' middleware.
 * 
 * FAIL CLOSED: Any validation failure logs out the user immediately.
 */
class ValidateSessionContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if user not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Get UserContext from session
        $contextResolver = app(\App\Services\Auth\UserContextResolver::class);
        $context = $contextResolver->getFromSession();

        // If no context in session, this is suspicious
        // User is authenticated but no frozen context exists
        if (!$context) {
            \Log::warning('[SECURITY] Authenticated user without session context', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Force logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['session' => 'Votre session est invalide. Veuillez vous reconnecter.']);
        }

        // Validate session context
        $isValid = $contextResolver->validateSession($user, $context);

        if (!$isValid) {
            \Log::warning('[SECURITY] Session context validation failed - forcing logout', [
                'user_id' => $user->id,
                'session_role' => $context->role,
                'ip' => $request->ip(),
            ]);

            // Force logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['session' => 'Votre session a expiré ou est invalide. Veuillez vous reconnecter.']);
        }

        // Context is valid, continue
        return $next($request);
    }
}
