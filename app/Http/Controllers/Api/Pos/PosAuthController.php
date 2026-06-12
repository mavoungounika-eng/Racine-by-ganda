<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosLoginRequest;
use App\Models\User;
use App\Services\Pos\PosConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * PosAuthController — login de l'API POS Connect (Electron / Sanctum).
 *
 * POST /api/pos/v1/login (alias POST /api/pos/login)
 * → { success: true, data: { token, creator, stripe_publishable_key } }
 *
 * Le login exige un créateur actif disposant d'un abonnement Signature
 * (plan has_pos) — même règle que le middleware signature.subscription.
 */
class PosAuthController extends Controller
{
    public function __construct(
        protected PosConnectService $posConnect,
    ) {
    }

    public function login(PosLoginRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est désactivé.',
            ], 403);
        }

        if (! $this->posConnect->hasPosAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => PosConnectService::ACCESS_DENIED_MESSAGE,
            ], 403);
        }

        $token = $user->createToken('pos-electron', ['pos:operate'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'token'   => $token,
                'creator' => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'brand_name' => $user->creatorProfile?->brand_name,
                ],
                'stripe_publishable_key' => config('services.stripe.key'),
            ],
        ]);
    }
}
