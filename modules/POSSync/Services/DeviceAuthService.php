<?php

namespace Modules\POSSync\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Modules\POSSync\Models\PosDevice;

class DeviceAuthService
{
    /**
     * Générer un token JWT pour une machine POS
     * 
     * @param string $machineId
     * @return string JWT token
     */
    public function generateToken(string $machineId): string
    {
        $device = PosDevice::where('machine_id', $machineId)->firstOrFail();

        $payload = [
            'iss' => config('app.name'),
            'sub' => $machineId,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60), // 7 jours
            'device_id' => $device->id,
            'device_name' => $device->name,
            'permissions' => ['sync_events', 'download_catalog'],
        ];

        return JWT::encode($payload, config('jwt.secret'), 'HS256');
    }

    /**
     * Valider un token JWT et retourner le device
     * 
     * @param string|null $token
     * @return PosDevice|null
     */
    public function validateToken(?string $token): ?PosDevice
    {
        if (!$token) {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));
            
            // Vérifier que le device existe et est actif
            $device = PosDevice::where('machine_id', $decoded->sub)->first();
            
            if (!$device) {
                return null;
            }

            // Vérifier si le token est en blacklist (révoqué)
            if ($this->isTokenBlacklisted($decoded->sub, $decoded->iat)) {
                return null;
            }

            return $device;

        } catch (\Exception $e) {
            \Log::warning('JWT validation failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return null;
        }
    }

    /**
     * Bloquer un device
     * 
     * @param string $machineId
     * @param string $reason
     * @return void
     */
    public function blockDevice(string $machineId, string $reason): void
    {
        $device = PosDevice::where('machine_id', $machineId)->first();
        
        if ($device) {
            $device->block($reason);
            
            // Notifier admin (email + SMS si critique)
            if (str_contains($reason, 'signature') || str_contains($reason, 'fraud')) {
                \Notification::route('mail', config('pos.admin_email'))
                    ->notify(new \Modules\POSSync\Notifications\DeviceBlockedNotification($device, $reason));
            }
        }
    }

    /**
     * Débloquer un device
     * 
     * @param string $machineId
     * @return void
     */
    public function unblockDevice(string $machineId): void
    {
        $device = PosDevice::where('machine_id', $machineId)->first();
        
        if ($device) {
            $device->unblock();
        }
    }

    /**
     * Révoquer un token (blacklist Redis)
     * 
     * @param string $machineId
     * @param int $issuedAt Timestamp IAT du JWT
     * @return void
     */
    public function revokeToken(string $machineId, int $issuedAt): void
    {
        $key = "jwt:blacklist:{$machineId}:{$issuedAt}";
        $ttl = 7 * 24 * 60 * 60; // 7 jours (durée de vie max du JWT)
        
        \Redis::setex($key, $ttl, '1');
    }

    /**
     * Vérifier si un token est en blacklist
     * 
     * @param string $machineId
     * @param int $issuedAt
     * @return bool
     */
    private function isTokenBlacklisted(string $machineId, int $issuedAt): bool
    {
        $key = "jwt:blacklist:{$machineId}:{$issuedAt}";
        return \Redis::exists($key);
    }

    /**
     * Renouveler un token JWT
     * 
     * @param string $oldToken
     * @return string|null Nouveau token ou null si échec
     */
    public function refreshToken(string $oldToken): ?string
    {
        $device = $this->validateToken($oldToken);
        
        if (!$device || !$device->isActive()) {
            return null;
        }

        // Générer nouveau token
        return $this->generateToken($device->machine_id);
    }
}
