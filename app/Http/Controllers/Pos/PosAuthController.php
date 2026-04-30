<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\PosApiResponse;
use App\Traits\AuditsPosOperations;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
     * Renouveler le JWT device (même si expiré, tant que la signature est valide).
     *
     * Ce endpoint existe pour deux raisons :
     * 1. Le JWT du terminal expire après 7 jours — le POS doit pouvoir le renouveler sans se réenregistrer.
     * 2. Après une migration DB, le device doit se ré-enregistrer : si le device n'existe plus,
     *    on renvoie 401 et le frontend efface le token pour déclencher un nouvel enregistrement.
     */
    public function refreshDeviceToken(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return PosApiResponse::unauthorized('Missing bearer token');
        }

        $secret = config('jwt.secret');
        $algo   = config('jwt.algo', 'HS256');

        if (empty($secret)) {
            return PosApiResponse::error('SERVER_ERROR', 'JWT not configured', [], 500);
        }

        // Extraire le machine_id même si le token est expiré
        $machineId = null;
        try {
            $decoded   = JWT::decode($token, new Key($secret, $algo));
            $machineId = $decoded->sub ?? null;
        } catch (ExpiredException $e) {
            // Token expiré mais signature valide — extraire le payload manuellement
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload   = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                $machineId = $payload['sub'] ?? null;
            }
        } catch (\Exception $e) {
            return PosApiResponse::unauthorized('Invalid token');
        }

        if (!$machineId) {
            return PosApiResponse::unauthorized('Invalid token payload');
        }

        $device = PosDevice::where('machine_id', $machineId)->first();

        if (!$device) {
            // Device supprimé de la DB → frontend doit se ré-enregistrer
            return PosApiResponse::unauthorized('Device not registered');
        }

        if (!$device->isActive()) {
            return PosApiResponse::error('DEVICE_NOT_ACTIVE', 'Device not active', ['status' => $device->status], 403);
        }

        $newToken = app(DeviceAuthService::class)->generateToken($machineId);

        return PosApiResponse::success(['token' => $newToken], 'Token refreshed');
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
