<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\TrustedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrustedDeviceService
{
    const COOKIE_NAME = 'trusted_device';
    const DAYS = 30;

    public function isTrusted(User $user, Request $request): bool
    {
        $token = $request->cookie(self::COOKIE_NAME);
        if (!$token) return false;

        return TrustedDevice::where('user_id', $user->id)
            ->where('device_token', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function trustDevice(User $user, Request $request): string
    {
        $token = Str::random(64);

        TrustedDevice::create([
            'user_id'      => $user->id,
            'device_token' => hash('sha256', $token),
            'device_name'  => $this->detectDevice($request),
            'ip_address'   => $request->ip(),
            'last_used_at' => now(),
            'expires_at'   => now()->addDays(self::DAYS),
        ]);

        return $token;
    }

    public function revokeAll(User $user): void
    {
        TrustedDevice::where('user_id', $user->id)->delete();
    }

    public function pruneExpired(): int
    {
        return TrustedDevice::where('expires_at', '<', now())->delete();
    }

    private function detectDevice(Request $request): string
    {
        $ua = $request->userAgent() ?? '';

        $browser = match(true) {
            str_contains($ua, 'Chrome')  => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari')  => 'Safari',
            str_contains($ua, 'Edge')    => 'Edge',
            default                      => 'Navigateur inconnu',
        };

        $os = match(true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac')     => 'Mac',
            str_contains($ua, 'iPhone')  => 'iPhone',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux')   => 'Linux',
            default                      => 'Appareil inconnu',
        };

        return "{$browser} sur {$os}";
    }
}
