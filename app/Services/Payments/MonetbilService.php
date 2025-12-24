<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de paiement Mobile Money via Monetbil Widget API v2.1
 */
class MonetbilService
{
    protected string $serviceKey;
    protected string $serviceSecret;
    protected string $widgetVersion;
    protected string $country;
    protected string $currency;
    protected string $notifyUrl;
    protected string $returnUrl;
    protected ?array $allowedIps;

    public function __construct()
    {
        $config = config('services.monetbil');
        
        $this->serviceKey = $config['service_key'] ?? '';
        $this->serviceSecret = $config['service_secret'] ?? '';
        $this->widgetVersion = $config['widget_version'] ?? 'v2.1';
        $this->country = $config['country'] ?? 'CG';
        $this->currency = $config['currency'] ?? 'XAF';
        $this->notifyUrl = $config['notify_url'] ?? '';
        $this->returnUrl = $config['return_url'] ?? '';
        $this->allowedIps = !empty($config['allowed_ips']) 
            ? explode(',', $config['allowed_ips']) 
            : null;
    }

    /**
     * Créer une URL de paiement Monetbil
     *
     * @param array $payload Données du paiement
     * @return string URL de paiement
     * @throws PaymentException Si la création échoue
     */
    public function createPaymentUrl(array $payload): string
    {
        if (empty($this->serviceKey)) {
            throw new PaymentException(
                'Monetbil non configuré',
                500,
                'Le paiement Mobile Money est actuellement désactivé. Veuillez contacter le support.'
            );
        }

        $url = "https://api.monetbil.com/widget/{$this->widgetVersion}/{$this->serviceKey}";

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Monetbil API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                throw new PaymentException(
                    'Erreur lors de la création du paiement Monetbil',
                    422, // Unprocessable Entity (erreur API attendue)
                    'Impossible de créer le paiement. Veuillez réessayer.'
                );
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                $message = $data['message'] ?? 'Erreur inconnue';
                Log::error('Monetbil payment creation failed', [
                    'message' => $message,
                    'data' => $data,
                ]);

                throw new PaymentException(
                    'Échec de la création du paiement',
                    422, // Unprocessable Entity (erreur API attendue)
                    $message
                );
            }

            if (empty($data['payment_url'])) {
                Log::error('Monetbil API: Missing payment_url in response', [
                    'response' => $data,
                ]);

                throw new PaymentException(
                    'URL de paiement manquante',
                    422, // Unprocessable Entity (erreur API attendue)
                    'La réponse de Monetbil ne contient pas d\'URL de paiement.'
                );
            }

            return $data['payment_url'];
        } catch (\Exception $e) {
            if ($e instanceof PaymentException) {
                throw $e;
            }

            Log::error('Monetbil service error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PaymentException(
                'Erreur de connexion à Monetbil',
                500,
                'Impossible de se connecter au service de paiement. Veuillez réessayer.'
            );
        }
    }

    /**
     * Vérifier la signature d'une notification Monetbil
     * 
     * RBG-P0-010 : Signature obligatoire en production
     * - Si signature absente en production => retourne false
     * - Si signature invalide => retourne false
     * - Utilise hash_equals() pour comparaison timing-safe
     *
     * @param array $params Paramètres de la notification
     * @return bool True si la signature est valide
     */
    public function verifySignature(array $params): bool
    {
        // Si pas de signature, refuser en production
        if (!isset($params['sign'])) {
            $isProduction = app()->environment('production') || config('app.env') === 'production';
            
            if ($isProduction) {
                Log::warning('Monetbil notification without signature in production', [
                    'reason' => 'missing_signature',
                ]);
                return false;
            }
            
            // En développement, tolérer l'absence de signature
            Log::info('Monetbil notification without signature (development mode)', [
                'reason' => 'missing_signature_dev',
            ]);
            return true;
        }

        $signature = $params['sign'];
        unset($params['sign']);

        // Trier les paramètres par clé
        ksort($params);

        // Construire la chaîne à hasher
        $values = array_values($params);
        $stringToHash = $this->serviceSecret . implode('', $values);

        // Calculer le hash MD5
        $calculatedHash = md5($stringToHash);

        // Comparer les signatures (timing-safe)
        $isValid = hash_equals($calculatedHash, $signature);

        if (!$isValid) {
            Log::warning('Monetbil signature verification failed', [
                'reason' => 'invalid_signature',
                // Ne jamais logger le secret ou la signature complète
            ]);
        }

        return $isValid;
    }

    /**
     * Normaliser le statut Monetbil vers notre format interne
     *
     * @param string $status Statut reçu de Monetbil
     * @return string Statut normalisé (success/cancelled/failed)
     */
    public function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'success', 'successful', 'paid', 'completed' => 'success',
            'cancelled', 'canceled', 'aborted' => 'cancelled',
            'failed', 'error', 'rejected' => 'failed',
            default => 'failed',
        };
    }

    /**
     * Vérifier si une IP est autorisée (si whitelist configurée)
     *
     * @param string $ip Adresse IP à vérifier
     * @return bool True si autorisée
     */
    public function isIpAllowed(string $ip): bool
    {
        if ($this->allowedIps === null) {
            return true; // Pas de whitelist = toutes les IPs autorisées
        }

        return in_array($ip, $this->allowedIps, true);
    }
}

