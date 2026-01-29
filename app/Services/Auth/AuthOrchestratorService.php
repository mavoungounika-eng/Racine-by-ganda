<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthResult;
use App\DTOs\Auth\UserContext;
use App\Models\User;
use App\Services\AuthLogger;
use App\Services\LoginAttemptService;
use App\Services\SessionSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Authentication Orchestrator Service
 * 
 * THE SINGLE ENTRY POINT for all authentication operations.
 * 
 * Responsibilities:
 * - Coordinate all authentication steps
 * - Manage login attempts and brute force protection
 * - Handle CAPTCHA requirements
 * - Execute Auth::attempt()
 * - Resolve UserContext via UserContextResolver
 * - Determine redirect via PostLoginDecisionEngine
 * - Handle 2FA flow
 * - Manage session security
 * - Log authentication events
 * 
 * CRITICAL: Controllers should ONLY call this service, never implement auth logic directly.
 */
class AuthOrchestratorService
{
    public function __construct(
        private UserContextResolver $contextResolver,
        private PostLoginDecisionEngine $decisionEngine,
        private AuthLogger $authLogger,
        private LoginAttemptService $attemptService,
        private SessionSecurityService $sessionSecurity,
    ) {}

    /**
     * Authenticate user with credentials
     * 
     * This is the MAIN entry point for login operations.
     */
    public function authenticate(Request $request, array $credentials, bool $remember = false): AuthResult
    {
        $email = $credentials['email'] ?? '';
        $ipAddress = $request->ip();

        // Step 1: Check if CAPTCHA is required
        if ($this->requiresCaptcha($email, $ipAddress)) {
            if (!$this->validateCaptcha($request)) {
                return AuthResult::captchaRequired();
            }
        }

        // Step 2: Check rate limiting
        if ($this->isRateLimited($email, $ipAddress)) {
            return AuthResult::failed(
                ['email' => 'Trop de tentatives de connexion. Veuillez réessayer dans quelques minutes.'],
                ['rate_limited' => true]
            );
        }

        // Step 3: Attempt authentication
        if (!Auth::attempt($credentials, $remember)) {
            // Record failed attempt
            $this->recordFailedAttempt($email, $ipAddress);

            return AuthResult::failed(
                ['email' => 'Les identifiants fournis sont incorrects.'],
                ['attempts_remaining' => $this->getRemainingAttempts($email, $ipAddress)]
            );
        }

        // Step 4: Authentication successful - get user
        $user = Auth::user();

        // Step 5: Clear failed attempts
        $this->clearFailedAttempts($email, $ipAddress);

        // Step 6: Resolve UserContext (SINGLE SOURCE OF TRUTH)
        try {
            $context = $this->contextResolver->resolve($user);
        } catch (\Throwable $e) {
            // Failed to resolve context - logout and fail
            Auth::logout();
            
            \Log::error('Failed to resolve UserContext during login', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return AuthResult::failed(
                ['email' => 'Erreur lors de la connexion. Veuillez contacter le support.'],
                ['context_resolution_failed' => true]
            );
        }

        // Step 7: Regenerate session for security FIRST
        // CRITICAL: Must regenerate BEFORE storing context to preserve it
        $request->session()->regenerate();

        // Step 8: Store context in session (now in fresh session)
        $this->contextResolver->storeInSession($context);

        // Step 9: Apply session security
        $this->sessionSecurity->initializeSessionTracking($user);

        // Step 11: Check if 2FA is required
        if ($this->decisionEngine->should2FAVerify($context)) {
            // Log 2FA challenge
            $this->authLogger->logLoginAttempt($user->email, true, $request->ip());

            return AuthResult::twoFactorRequired(
                $user,
                $this->decisionEngine->get2FAVerificationUrl()
            );
        }

        // Step 11: Determine redirect URL
        $intended = $request->session()->pull('url.intended');
        $redirectUrl = $this->decisionEngine->determineRedirect($context, $intended);

        // Step 12: Log successful login
        $this->authLogger->logLoginAttempt($user->email, true, $request->ip());

        // Step 13: Return success result
        return AuthResult::success($user, $redirectUrl);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): string
    {
        // Get context before logout (for redirect decision)
        $context = $this->contextResolver->getFromSession();

        // Get user before logout (for logging)
        $user = Auth::user();

        // Logout
        Auth::logout();

        // Clear user context from session
        $this->contextResolver->clearFromSession();

        // Invalidate session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Log logout
        if ($user) {
            $this->authLogger->logLogout($user);
        }

        // Determine logout redirect
        return $this->decisionEngine->determineLogoutRedirect($context);
    }

    /**
     * Check if CAPTCHA is required for this email/IP
     */
    private function requiresCaptcha(string $email, string $ipAddress): bool
    {
        // Require CAPTCHA after 3 failed attempts
        return $this->attemptService->getAttempts($email) >= 3;
    }

    /**
     * Validate CAPTCHA from request
     */
    private function validateCaptcha(Request $request): bool
    {
        // TODO: Implement actual CAPTCHA validation (reCAPTCHA, hCaptcha, etc.)
        // For now, just check if captcha field is present
        return $request->filled('captcha_token');
    }

    /**
     * Check if email/IP is rate limited
     */
    private function isRateLimited(string $email, string $ipAddress): bool
    {
        $key = 'login:' . $email . ':' . $ipAddress;
        
        return RateLimiter::tooManyAttempts($key, 5);
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $email, string $ipAddress): void
    {
        // Record in LoginAttemptService
        $this->attemptService->recordFailedAttempt($email);

        // Also use Laravel's rate limiter
        $key = 'login:' . $email . ':' . $ipAddress;
        RateLimiter::hit($key, 60); // 60 seconds decay
    }

    /**
     * Clear failed attempts after successful login
     */
    private function clearFailedAttempts(string $email, string $ipAddress): void
    {
        $this->attemptService->clearAttempts($email);

        $key = 'login:' . $email . ':' . $ipAddress;
        RateLimiter::clear($key);
    }

    /**
     * Get remaining attempts before lockout
     */
    private function getRemainingAttempts(string $email, string $ipAddress): int
    {
        $key = 'login:' . $email . ':' . $ipAddress;
        $attempts = RateLimiter::attempts($key);
        
        return max(0, 5 - $attempts);
    }

    /**
     * Validate existing session context
     * 
     * This will be used by middleware to ensure session is still valid.
     */
    public function validateSessionContext(Request $request): bool
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Get context from session
        $context = $this->contextResolver->getFromSession();

        if (!$context) {
            // No context in session - invalid
            return false;
        }

        // Validate context matches current user
        return $this->contextResolver->validateSession($user, $context);
    }

    /**
     * Refresh user context in session
     * 
     * Used when user data changes and we need to update the frozen context.
     */
    public function refreshContext(User $user): void
    {
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);
    }
}
