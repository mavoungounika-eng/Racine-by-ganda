<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Carbon\Carbon;

class SecurityController extends Controller
{
    /**
     * Afficher le dashboard de gestion des sessions
     */
    public function index(): View
    {
        $sessions = $this->getActiveSessions();

        return view('client.security', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Déconnecter une session spécifique
     */
    public function logoutOther(string $sessionId): JsonResponse
    {
        try {
            // Empêcher la déconnexion de la session actuelle
            if ($sessionId === session()->getId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas déconnecter votre session actuelle.',
                ], 400);
            }

            // Supprimer la session ciblée
            $deleted = DB::table('sessions')
                ->where('user_id', auth()->id())
                ->where('id', $sessionId)
                ->delete();

            if ($deleted === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session introuvable ou déjà déconnectée.',
                ], 404);
            }

            Log::info('Session révoquée par l\'utilisateur', [
                'user_id' => auth()->id(),
                'session_id' => $sessionId,
                'current_session' => session()->getId(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session déconnectée avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion de session', [
                'user_id' => auth()->id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion de la session.',
            ], 500);
        }
    }

    /**
     * Déconnecter toutes les autres sessions
     */
    public function logoutAllOthers(): JsonResponse
    {
        try {
            $deleted = DB::table('sessions')
                ->where('user_id', auth()->id())
                ->where('id', '!=', session()->getId())
                ->delete();

            Log::info('Toutes les autres sessions révoquées', [
                'user_id' => auth()->id(),
                'sessions_revoked' => $deleted,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Toutes les autres sessions ont été déconnectées ({$deleted} session(s)).",
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion des autres sessions', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion des autres sessions.',
            ], 500);
        }
    }

    /**
     * Récupérer les sessions actives de l'utilisateur
     */
    private function getActiveSessions(): array
    {
        $sessionsData = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderBy('last_activity', 'desc')
            ->get();

        return $sessionsData->map(function ($session) {
            $userAgent = $session->user_agent ?? '';
            $device = $this->parseDevice($userAgent);
            $browser = $this->parseBrowser($userAgent);

            return [
                'id' => $session->id,
                'ip' => $session->ip_address ?? 'N/A',
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'last_active' => Carbon::createFromTimestamp($session->last_activity),
                'is_current' => $session->id === session()->getId(),
            ];
        })->toArray();
    }

    /**
     * Parser le type d'appareil depuis le user agent
     */
    private function parseDevice(string $userAgent): string
    {
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            return 'Mobile';
        }

        if (stripos($userAgent, 'Tablet') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'Tablette';
        }

        return 'Ordinateur';
    }

    /**
     * Parser le navigateur depuis le user agent
     */
    private function parseBrowser(string $userAgent): string
    {
        if (stripos($userAgent, 'Edg') !== false) {
            return 'Microsoft Edge';
        }

        if (stripos($userAgent, 'Chrome') !== false) {
            return 'Google Chrome';
        }

        if (stripos($userAgent, 'Firefox') !== false) {
            return 'Mozilla Firefox';
        }

        if (stripos($userAgent, 'Safari') !== false) {
            return 'Safari';
        }

        if (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) {
            return 'Opera';
        }

        return 'Navigateur inconnu';
    }
}
