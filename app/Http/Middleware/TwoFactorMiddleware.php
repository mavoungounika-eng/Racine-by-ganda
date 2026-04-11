<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TwoFactorService;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    protected TwoFactorService $twoFactorService;
    
    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }
    
    /**
     * Handle an incoming request.
     * Vérifie si l'utilisateur doit passer par le challenge 2FA
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Vérifier si le 2FA est activé
        if (!$this->twoFactorService->isEnabled($user)) {
            // Si 2FA obligatoire mais pas activé, rediriger vers setup
            if ($this->twoFactorService->isRequired($user)) {
                return redirect()->route('2fa.setup')
                    ->with('warning', 'La double authentification est obligatoire pour votre compte. Veuillez la configurer.');
            }
            return $next($request);
        }
        
        // Vérifier si la session a déjà été validée pour le 2FA
        if (session()->has('2fa_verified') && session('2fa_verified') === true) {
            return $next($request);
        }
        
        // Vérifier si l'appareil est de confiance
        $trustedToken = $request->cookie('trusted_device');
        if ($trustedToken && $this->twoFactorService->isTrustedDevice($user, $trustedToken)) {
            session(['2fa_verified' => true]);
            return $next($request);
        }
        
        // Stocker l'info utilisateur et rediriger vers le challenge
        session([
            '2fa_user_id' => $user->id,
            '2fa_remember' => $request->boolean('remember'),
        ]);
        
        // Déconnecter temporairement
        Auth::logout();
        
        return redirect()->route('2fa.challenge');
    }
}

