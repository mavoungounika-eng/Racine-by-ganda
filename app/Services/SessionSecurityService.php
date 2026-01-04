<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service de sécurité des sessions
 * 
 * PHASE 2 SÉCURITÉ : Détection d'anomalies de session
 * Mode passif : détecte et logue, ne bloque pas automatiquement
 */
class SessionSecurityService
{
    /**
     * Détecter les anomalies de session (changement IP ou User-Agent)
     * 
     * @param User $user
     * @return bool True si anomalie détectée
     */
    public function detectAnomalies(User $user): bool
    {
        $currentIp = request()->ip();
        $currentUA = request()->userAgent();
        
        $lastIp = session('last_ip');
        $lastUA = session('last_user_agent');
        
        $anomalyDetected = false;
        
        // Détection changement IP
        if ($lastIp && $lastIp !== $currentIp) {
            $this->logAnomaly($user, 'ip_change', [
                'previous_ip' => $lastIp,
                'current_ip' => $currentIp,
            ]);
            $anomalyDetected = true;
        }
        
        // Détection changement User-Agent
        if ($lastUA && $lastUA !== $currentUA) {
            $this->logAnomaly($user, 'user_agent_change', [
                'previous_ua' => $lastUA,
                'current_ua' => $currentUA,
            ]);
            $anomalyDetected = true;
        }
        
        // Mettre à jour les valeurs de session
        session([
            'last_ip' => $currentIp,
            'last_user_agent' => $currentUA,
        ]);
        
        return $anomalyDetected;
    }
    
    /**
     * Initialiser le tracking de session
     * À appeler après un login réussi
     * 
     * @param User $user
     */
    public function initializeSessionTracking(User $user): void
    {
        session([
            'last_ip' => request()->ip(),
            'last_user_agent' => request()->userAgent(),
            'session_started_at' => now()->toIso8601String(),
        ]);
        
        Log::channel('auth')->info('SESSION_INITIALIZED', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Logger une anomalie détectée
     * 
     * @param User $user
     * @param string $type
     * @param array $context
     */
    private function logAnomaly(User $user, string $type, array $context = []): void
    {
        Log::channel('auth')->warning('ANOMALY_DETECTED', [
            'user_id' => $user->id,
            'email' => $user->email,
            'anomaly_type' => $type,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
            ...$context
        ]);
    }
    
    /**
     * Vérifier si l'utilisateur est un membre de l'équipe
     * (admin/super_admin nécessitent surveillance accrue)
     * 
     * @param User $user
     * @return bool
     */
    public function isHighValueTarget(User $user): bool
    {
        return $user->isTeamMember();
    }
}
