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

    public function __construct(?array $config = null)
    {
        $config = $config ?? config('services.monetbil');
        
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
     * Configurer dynamiquement les clés pour un paiement spécifique (SaaS Pur)
     */
    public function setServiceKeys(string $key, string $secret): self
    {
        $this->serviceKey = $key;
        $this->serviceSecret = $secret;
        return $this;
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
     * @param array $params Paramètres de la notification
     * @param string|null $dynamicSecret Secret spécifique au créateur (SaaS Pur)
     * @return bool True si la signature est valide
     */
    public function verifySignature(array $params, ?string $dynamicSecret = null): bool
    {
        $secretToUse = $dynamicSecret ?? $this->serviceSecret;

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
        $stringToHash = $secretToUse . implode('', $values);

        // Calculer le hash MD5
        $calculatedHash = md5($stringToHash);

        // Comparer les signatures (timing-safe)
        $isValid = hash_equals($calculatedHash, $signature);

        if (!$isValid) {
            Log::warning('Monetbil signature verification failed', [
                'reason' => 'invalid_signature',
                'using_dynamic_secret' => !empty($dynamicSecret),
            ]);
        }

        return $isValid;
    }

    /**
     * Normaliser le statut Monetbil vers notre format interne.
     *
     * Codes numériques Monetbil :
     *   Production : 1 = success, -1 = cancelled,  0 = failed
     *   Test       : 7 = success,  8 = failed,      9 = cancelled
     *
     * @param string|int $status Statut reçu de Monetbil (numérique ou texte)
     * @return string 'success' | 'cancelled' | 'failed'
     */
    public function normalizeStatus(string|int $status): string
    {
        // Codes numériques (production et test)
        if (is_numeric($status)) {
            return match ((int) $status) {
                1, 7    => 'success',
                -1, 9   => 'cancelled',
                default => 'failed',  // 0, 8 et tout inconnu
            };
        }

        return match (strtolower(trim((string) $status))) {
            'success', 'successful', 'paid', 'completed' => 'success',
            'cancelled', 'canceled', 'aborted'           => 'cancelled',
            default                                       => 'failed',
        };
    }

    /**
     * Vérifier le statut d'un paiement via l'API Monetbil checkPayment.
     *
     * Utile pour les cas où le webhook n'est pas reçu (timeout, réseau).
     *
     * @param string $paymentRef Référence de paiement (order_number)
     * @return array{status: string, amount: mixed, currency: mixed, operator: mixed, transaction_id: mixed, raw: array}
     */
    public function checkPayment(string $paymentRef): array
    {
        $baseUrl = config('services.monetbil.base_url', 'https://api.monetbil.com/payment/v1.1');

        try {
            $response = Http::timeout(15)->post("{$baseUrl}/checkPayment", [
                'serviceKey' => $this->serviceKey,
                'paymentRef' => $paymentRef,
            ]);

            if (!$response->successful()) {
                Log::error('Monetbil checkPayment HTTP error', [
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                    'paymentRef' => $paymentRef,
                ]);
                return ['status' => 'unknown', 'raw' => []];
            }

            $data = $response->json() ?? [];

            return [
                'status'         => $this->normalizeStatus($data['status'] ?? 0),
                'amount'         => $data['amount'] ?? null,
                'currency'       => $data['currency'] ?? null,
                'operator'       => $data['operator'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'raw'            => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Monetbil checkPayment exception', [
                'paymentRef' => $paymentRef,
                'error'      => $e->getMessage(),
            ]);
            return ['status' => 'unknown', 'raw' => []];
        }
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

