<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * Listener pour limiter le nombre de sessions actives par utilisateur
 * 
 * Supprime automatiquement les sessions les plus anciennes si l'utilisateur
 * dépasse le nombre maximum de sessions autorisées (3 par défaut).
 */
class LogSuccessfulLogin
{
    private const MAX_SESSIONS = 3;

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Compter les sessions actives pour cet utilisateur
        $activeSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->count();

        // Si le nombre de sessions dépasse la limite, supprimer les plus anciennes
        if ($activeSessions > self::MAX_SESSIONS) {
            $sessionsToDelete = $activeSessions - self::MAX_SESSIONS;

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderBy('last_activity', 'asc')
                ->limit($sessionsToDelete)
                ->delete();
        }
    }
}
