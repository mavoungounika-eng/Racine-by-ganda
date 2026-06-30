<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectUserCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $supported = config('currency.supported', ['XAF']);

            // 1. Priorité : query param ?currency=XOF
            if ($request->has('currency') && in_array($request->currency, $supported)) {
                session(['currency' => $request->currency]);
                if (auth()->check()) {
                    auth()->user()->update([
                        'preferred_currency' => $request->currency
                    ]);
                }
            }
            // 2. User connecté → preferred_currency
            elseif (auth()->check() && auth()->user()->preferred_currency) {
                session([
                    'currency' => auth()->user()->preferred_currency
                ]);
            }
            // 3. Session existante → conserver
            // 4. Fallback → XAF
            elseif (!session('currency')) {
                session(['currency' => config('currency.default', 'XAF')]);
            }
        } catch (\Throwable $e) {
            // Fallback silencieux
            session(['currency' => 'XAF']);
        }

        return $next($request);
    }
}
