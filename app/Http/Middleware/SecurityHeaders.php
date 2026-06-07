<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Générer un nonce pour CSP
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        // Partager le nonce via config() et View::share() pour garantir
        // l'accès dans tous les contextes Blade (routes authentifiées incluses)
        config(['csp.nonce' => $nonce]);
        \Illuminate\Support\Facades\View::share('cspNonce', $nonce);

        $response = $next($request);

        // Headers de sécurité HTTP de base
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Headers modernes (Isolation)
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        // Cross-Origin-Embedder-Policy désactivé : 'require-corp' bloque les iframes
        // externes comme Google Maps. À réactiver uniquement si SharedArrayBuffer est requis.
        // $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        // HSTS (Strict Transport Security)
        if ($request->secure() || env('FORCE_HTTPS', false)) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        // Content Security Policy (CSP) - Hardened
        // En local, on autorise le serveur Vite HMR (port 5173) pour le hot-reload
        $isLocal      = app()->environment('local');
        $scriptExtra  = $isLocal ? ' http://127.0.0.1:5173' : '';
        $styleExtra   = $isLocal ? ' http://127.0.0.1:5173' : '';
        $connectExtra = $isLocal ? ' http://127.0.0.1:5173 ws://127.0.0.1:5173' : '';

        $isSecure = $request->secure() || env('FORCE_HTTPS', false);

        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://js.stripe.com https://cdn.tiny.cloud https://www.google.com https://www.gstatic.com{$scriptExtra}; " .
               "style-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com{$styleExtra}; " .
               "style-src-attr 'unsafe-inline'; " .
               "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https: http:; " .
               "connect-src 'self' https://api.stripe.com https://api.openai.com https://api.anthropic.com https://api.groq.com{$connectExtra}; " .
               "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://www.google.com https://maps.googleapis.com https://maps.google.com; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'; " .
               "frame-ancestors 'none';" .
               ($isSecure ? " upgrade-insecure-requests;" : "");

        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy
        $response->headers->set('Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=()'
        );

        return $response;
    }
}