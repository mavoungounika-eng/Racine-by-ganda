<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\PosApiResponse;
use App\Traits\AuditsPosOperations;
use Modules\POSSync\Models\PosDevice;
use Modules\POSSync\Services\DeviceAuthService;

class PosAuthController extends Controller
{
    use AuditsPosOperations;

    /**
     * Register a new POS terminal device.
     *
     * Idempotent: if machine_id already exists, returns the existing device + fresh JWT.
     */
    public function registerTerminal(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        $deviceAuthService = app(DeviceAuthService::class);

        $device = PosDevice::where('machine_id', $request->machine_id)->first();

        if (!$device) {
            $machineSecret = bin2hex(random_bytes(32));

            $device = PosDevice::create([
                'machine_id' => $request->machine_id,
                'name' => $request->name,
                'machine_secret' => $machineSecret,
                'status' => 'active',
            ]);
        }

        $token = $deviceAuthService->generateToken($device->machine_id);

        self::logPosAction('TERMINAL_REGISTER', [
            'machine_id' => $device->machine_id,
            'name' => $device->name,
            'status' => $device->status,
        ]);

        return PosApiResponse::success([
            'device' => [
                'id' => $device->id,
                'machine_id' => $device->machine_id,
                'name' => $device->name,
                'status' => $device->status,
            ],
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
