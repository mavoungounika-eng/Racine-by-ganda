<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    /**
     * Check if reCAPTCHA is enabled.
     */
    public function isEnabled(): bool
    {
        // Désactiver automatiquement en tests
        if (config('recaptcha.skip_for_testing')) {
            return false;
        }

        // Vérifier que les clés sont configurées
        return config('recaptcha.enabled', false) 
            && !empty(config('recaptcha.secret_key'))
            && !empty(config('recaptcha.site_key'));
    }

    /**
     * Verify reCAPTCHA token.
     *
     * @param string $token Token from frontend
     * @param string $action Expected action name (default: 'login')
     * @return bool True if verification passes
     */
    public function verify(string $token, string $action = 'login'): bool
    {
        // Bypass si désactivé
        if (!$this->isEnabled()) {
            Log::info('reCAPTCHA bypassed (disabled)');
            return true;
        }

        // Valider token non vide
        if (empty($token)) {
            Log::warning('reCAPTCHA token is empty');
            return false;
        }

        try {
            // Appel API Google
            $response = Http::timeout(config('recaptcha.timeout', 5))
                ->asForm()
                ->post(config('recaptcha.verify_url'), [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            // Vérifier succès HTTP
            if (!$response->successful()) {
                Log::error('reCAPTCHA API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return true; // Fail-open: laisser passer en cas d'erreur API
            }

            $data = $response->json();

            // Vérifier succès reCAPTCHA
            if (!($data['success'] ?? false)) {
                Log::warning('reCAPTCHA verification failed', [
                    'error_codes' => $data['error-codes'] ?? [],
                    'token' => substr($token, 0, 20) . '...',
                ]);
                return false;
            }

            // Vérifier action (optionnel mais recommandé)
            if (isset($data['action']) && $data['action'] !== $action) {
                Log::warning('reCAPTCHA action mismatch', [
                    'expected' => $action,
                    'received' => $data['action'],
                ]);
                return false;
            }

            // Vérifier score
            $score = $data['score'] ?? 0.0;
            $threshold = config('recaptcha.threshold');
            $passed = $score >= $threshold;

            // Log résultat
            Log::info('reCAPTCHA verification completed', [
                'score' => $score,
                'threshold' => $threshold,
                'action' => $data['action'] ?? null,
                'passed' => $passed,
                'hostname' => $data['hostname'] ?? null,
            ]);

            return $passed;

        } catch (\Exception $e) {
            // Fail-open: En cas d'erreur, laisser passer
            Log::error('reCAPTCHA verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return true; // Fail-open pour éviter de bloquer les utilisateurs
        }
    }

    /**
     * Get reCAPTCHA score for a token.
     *
     * @param string $token Token from frontend
     * @return float Score between 0.0 and 1.0
     */
    public function getScore(string $token): float
    {
        if (!$this->isEnabled() || empty($token)) {
            return 1.0; // Score maximum si désactivé
        }

        try {
            $response = Http::timeout(config('recaptcha.timeout', 5))
                ->asForm()
                ->post(config('recaptcha.verify_url'), [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            if (!$response->successful()) {
                return 1.0; // Fail-open
            }

            $data = $response->json();
            return $data['score'] ?? 0.0;

        } catch (\Exception $e) {
            Log::error('reCAPTCHA score retrieval error', [
                'error' => $e->getMessage(),
            ]);
            return 1.0; // Fail-open
        }
    }

    /**
     * Get site key for frontend.
     *
     * @return string|null
     */
    public function getSiteKey(): ?string
    {
        return $this->isEnabled() ? config('recaptcha.site_key') : null;
    }
}
