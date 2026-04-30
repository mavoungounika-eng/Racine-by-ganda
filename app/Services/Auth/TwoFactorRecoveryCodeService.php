<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * TwoFactorRecoveryCodeService - Gestion des codes de récupération 2FA
 * 
 * FONCTIONNALITÉS:
 * - Génération de codes de sauvegarde
 * - Validation et consommation des codes
 * - Storage des codes hashés (jamais en clair)
 * - Audit logging
 * 
 * USAGE:
 * $service = new TwoFactorRecoveryCodeService();
 * 
 * // Générer codes
 * $codes = $service->generateRecoveryCodes($user);
 * // Display to user with: "SAVE THESE NOW"
 * 
 * // Valider code de récupération
 * $success = $service->validateAndConsumeCode($user, $code);
 */
class TwoFactorRecoveryCodeService
{
    private const RECOVERY_CODE_COUNT = 10;
    private const RECOVERY_CODE_LENGTH = 8;

    /**
     * Générer 10 codes de récupération
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        
        // Générer codes en clair (pour display une seule fois)
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = $this->generateCode();
        }

        // Hasher et sauvegarder
        $hashedCodes = array_map(fn($code) => hash('sha256', $code), $codes);
        
        $user->update([
            'two_factor_recovery_codes' => json_encode($hashedCodes),
        ]);

        \Log::info('[2FA] Recovery codes generated', [
            'user_id' => $user->id,
            'code_count' => count($codes),
        ]);

        return $codes; // Retourner codes EN CLAIR pour affichage UNE FOIS
    }

    /**
     * Valider et consommer un code de récupération
     */
    public function validateAndConsumeCode(User $user, string $code): bool
    {
        $storedCodes = json_decode($user->two_factor_recovery_codes ?? '[]', true);

        if (empty($storedCodes)) {
            \Log::warning('[2FA] No recovery codes available', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        $codeHash = hash('sha256', $code);

        // Chercher le code
        $foundIndex = array_search($codeHash, $storedCodes, true);

        if ($foundIndex === false) {
            \Log::warning('[2FA] Invalid recovery code attempt', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // Retirer le code utilisé
        unset($storedCodes[$foundIndex]);
        $storedCodes = array_values($storedCodes); // Re-index array

        $user->update([
            'two_factor_recovery_codes' => json_encode($storedCodes),
        ]);

        \Log::info('[2FA] Recovery code consumed', [
            'user_id' => $user->id,
            'remaining_codes' => count($storedCodes),
        ]);

        // ALERTE: Codes faibles
        if (count($storedCodes) < 3) {
            \Log::warning('[2FA] Low recovery codes remaining', [
                'user_id' => $user->id,
                'remaining' => count($storedCodes),
            ]);
        }

        return true;
    }

    /**
     * Compter codes restants
     */
    public function getRemainingCodesCount(User $user): int
    {
        $codes = json_decode($user->two_factor_recovery_codes ?? '[]', true);
        return count($codes);
    }

    /**
     * Générer UN code de récupération
     */
    private function generateCode(): string
    {
        return strtoupper(Str::random(self::RECOVERY_CODE_LENGTH));
    }
};
