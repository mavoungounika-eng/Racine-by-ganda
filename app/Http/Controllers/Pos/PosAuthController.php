<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\PosApiResponse;
use App\Traits\AuditsPosOperations;

class PosAuthController extends Controller
{
    use AuditsPosOperations;

    /**
     * Register a new POS terminal device.
     */
    public function registerTerminal(Request $request): JsonResponse
    {
        \Log::info('POS Terminal Registration Request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'data' => $request->all(),
        ]);

        $request->validate([
            'machine_id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        // For now, we'll create a simple device record
        // In a real implementation, you might want to store this in a devices table
        $device = [
            'machine_id' => $request->machine_id,
            'name' => $request->name,
            'status' => 'active',
            'registered_at' => now(),
        ];

        // Create a device token (using Sanctum for simplicity)
        $token = 'pos-device-' . $request->machine_id . '-' . now()->timestamp;

        self::logPosAction('TERMINAL_REGISTER', [
            'machine_id' => $request->machine_id,
            'name' => $request->name,
        ]);

        \Log::info('POS Terminal Registration Success', ['device' => $device]);

        return PosApiResponse::success([
            'device' => $device,
            'token' => $token,
        ], 'Terminal registered successfully');
    }

    /**
     * Operator login using email/password + PosApiResponse envelope.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return PosApiResponse::unauthorized('Invalid credentials');
        }

        // Check if user is allowed to access POS
        if (!$user->isTeamMember()) {
            return PosApiResponse::error('FORBIDDEN', 'Role not allowed for POS', [], 403);
        }

        // Revoke existing POS tokens for this user
        $user->tokens()->where('name', 'pos-operator')->delete();

        // Create new Sanctum token
        $token = $user->createToken('pos-operator', ['pos:operate'])->plainTextToken;

        self::logPosAction('OPERATOR_LOGIN', [
            'operator_id' => $user->id,
            'email' => $user->email,
        ], $user->id);

        return PosApiResponse::success([
            'operator' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleSlug(),
            ],
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Operator logout (revokes token).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->posOperator ?? $request->user();

        if (!$user) {
            return PosApiResponse::unauthorized('Invalid operator token');
        }

        // Revoke current operator token from X-Operator-Token.
        if ($request->header('X-Operator-Token')) {
            $user->tokens()->where('tokenable_type', User::class)->where('tokenable_id', $user->id)->where('name', 'pos-operator')->delete();
        } elseif ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        self::logPosAction('OPERATOR_LOGOUT', [
            'operator_id' => $user->id,
            'email' => $user->email,
            'reason' => 'regular_logout',
        ], $user->id);

        return PosApiResponse::success(['message' => 'Logged out'], 'Logged out');
    }

    /**
     * Get current operator info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->posOperator ?? $request->user();

        if (!$user) {
            return PosApiResponse::unauthorized('Invalid operator token');
        }

        return PosApiResponse::success([
            'operator' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleSlug(),
            ],
        ], 'Operator profile retrieved');
    }
}
