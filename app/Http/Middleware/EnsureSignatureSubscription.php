<?php

namespace App\Http\Middleware;

use App\Services\Pos\PosConnectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureSignatureSubscription Middleware
 *
 * Réservé à l'API POS Connect (Electron) : vérifie que l'utilisateur
 * authentifié (Sanctum) est un créateur disposant d'un abonnement actif
 * dont le plan inclut le POS (has_pos = true — plan 'signature').
 *
 * Usage : ->middleware('signature.subscription')
 */
class EnsureSignatureSubscription
{
    public function __construct(
        protected PosConnectService $posConnect,
    ) {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->posConnect->hasPosAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => PosConnectService::ACCESS_DENIED_MESSAGE,
            ], 403);
        }

        return $next($request);
    }
}
