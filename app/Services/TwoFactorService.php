<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    protected Google2FA $google2fa;
    protected string $appName;
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->appName = config('app.name', 'RACINE BY GANDA');
    }
    
    /**
     * Génère un nouveau secret 2FA pour l'utilisateur
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }
    
    /**
     * Génère le QR Code SVG pour Google Authenticator
     */
    public function generateQrCodeSvg(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $this->appName,
            $user->email,
            $secret
        );
        
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        return $writer->writeString($qrCodeUrl);
    }
    
    /**
     * Génère l'URL pour le QR Code (format otpauth://)
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            $this->appName,
            $user->email,
            $secret
        );
    }
    
    /**
     * Vérifie un code 2FA
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }
    
    /**
     * Active le 2FA pour un utilisateur
     */
    public function enableTwoFactor(User $user, string $secret): bool
    {
        $recoveryCodes = $this->generateRecoveryCodes();
        
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->two_factor_confirmed_at = now();
        
        // Rendre obligatoire pour admin et super_admin
        if (in_array($user->getRoleSlug(), ['admin', 'super_admin'])) {
            $user->two_factor_required = true;
        }
        
        return $user->save();
    }
    
    /**
     * Désactive le 2FA pour un utilisateur
     */
    public function disableTwoFactor(User $user): bool
    {
        // Ne pas permettre la désactivation pour admin/super_admin
        if ($user->two_factor_required) {
            return false;
        }
        
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->trusted_device_token = null;
        $user->trusted_device_expires_at = null;
        
        return $user->save();
    }
    
    /**
     * Génère des codes de récupération
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(4) . '-' . Str::random(4));
        }
        
        return $codes;
    }
    
    /**
     * Régénère les codes de récupération
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        
        $user->two_factor_recovery_codes = encrypt(json_encode($codes));
        $user->save();
        
        return $codes;
    }
    
    /**
     * Récupère les codes de récupération d'un utilisateur
     */
    public function getRecoveryCodes(User $user): array
    {
        if (!$user->two_factor_recovery_codes) {
            return [];
        }
        
        try {
            return json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Vérifie un code de récupération
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->getRecoveryCodes($user);
        $code = strtoupper(str_replace(' ', '', $code));
        
        if (in_array($code, $codes)) {
            // Supprimer le code utilisé
            $codes = array_diff($codes, [$code]);
            $user->two_factor_recovery_codes = encrypt(json_encode(array_values($codes)));
            $user->save();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Vérifie si le 2FA est activé pour un utilisateur
     */
    public function isEnabled(User $user): bool
    {
        return !empty($user->two_factor_secret) && !empty($user->two_factor_confirmed_at);
    }
    
    /**
     * Vérifie si le 2FA est obligatoire pour un utilisateur
     */
    public function isRequired(User $user): bool
    {
        // En développement local, la 2FA n'est pas obligatoire
        if (app()->environment('local')) {
            return false;
        }
        
        // Obligatoire pour admin et super_admin
        return $user->two_factor_required || in_array($user->getRoleSlug(), ['admin', 'super_admin']);
    }
    
    /**
     * Récupère le secret décrypté
     */
    public function getDecryptedSecret(User $user): ?string
    {
        if (!$user->two_factor_secret) {
            return null;
        }
        
        try {
            return decrypt($user->two_factor_secret);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Génère un token d'appareil de confiance
     */
    public function generateTrustedDeviceToken(User $user, int $days = 30): string
    {
        $token = Str::random(64);
        
        $user->trusted_device_token = hash('sha256', $token);
        $user->trusted_device_expires_at = now()->addDays($days);
        $user->save();
        
        return $token;
    }
    
    /**
     * Vérifie si l'appareil est de confiance
     */
    public function isTrustedDevice(User $user, ?string $token): bool
    {
        if (!$token || !$user->trusted_device_token) {
            return false;
        }
        
        if ($user->trusted_device_expires_at && $user->trusted_device_expires_at < now()) {
            return false;
        }
        
        return hash_equals($user->trusted_device_token, hash('sha256', $token));
    }
    
    /**
     * Révoque l'appareil de confiance
     */
    public function revokeTrustedDevice(User $user): bool
    {
        $user->trusted_device_token = null;
        $user->trusted_device_expires_at = null;
        
        return $user->save();
    }
}
