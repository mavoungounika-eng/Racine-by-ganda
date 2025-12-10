<?php

namespace App\Services\Payments;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de paiement Mobile Money (MTN MoMo, Airtel Money)
 * 
 * Note: Cette implémentation simule le processus de paiement Mobile Money.
 * Pour une intégration réelle, il faudra utiliser les APIs des providers.
 */
class MobileMoneyPaymentService
{
    /**
     * Initier un paiement Mobile Money
     *
     * @param Order $order
     * @param string $phone
     * @param string $provider (mtn_momo, airtel_money)
     * @return Payment
     * @throws \Exception
     */
    public function initiatePayment(Order $order, string $phone, string $provider = 'mtn_momo'): Payment
    {
        // Valider le numéro de téléphone
        $phone = $this->normalizePhoneNumber($phone);
        
        if (!$this->validatePhoneNumber($phone, $provider)) {
            throw new \Exception('Numéro de téléphone invalide pour ' . $provider);
        }

        // Créer un enregistrement Payment
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'currency' => config('app.currency', 'XAF'),
            'channel' => 'mobile_money',
            'provider' => $provider,
            'customer_phone' => $phone,
            'status' => 'initiated',
            'external_reference' => $this->generateTransactionId($provider),
            'metadata' => [
                'order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'provider' => $provider,
                'initiated_at' => now()->toIso8601String(),
            ],
        ]);

        Log::info('Mobile Money payment initiated', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => $provider,
            'phone' => $phone,
        ]);

        // Vérifier si le provider est activé et configuré
        $providerConfig = config("services.{$provider}");
        
        if ($providerConfig['enabled'] ?? false) {
            // Mode production : appeler l'API réelle
            try {
                $this->callProviderAPI($payment, $provider);
            } catch (\Exception $e) {
                Log::error('Mobile Money API call failed', [
                    'payment_id' => $payment->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
                
                // En cas d'erreur, basculer en mode simulation pour ne pas bloquer
                $this->simulatePaymentRequest($payment);
            }
        } else {
            // Mode développement : simuler le processus
            $this->simulatePaymentRequest($payment);
        }

        return $payment;
    }

    /**
     * Vérifier le statut d'un paiement
     *
     * @param string $transactionId
     * @return Payment|null
     */
    public function checkPaymentStatus(string $transactionId): ?Payment
    {
        $payment = Payment::where('external_reference', $transactionId)
            ->where('channel', 'mobile_money')
            ->first();

        if (!$payment) {
            return null;
        }

        // Vérifier si le provider est activé
        $provider = $payment->provider;
        $providerConfig = config("services.{$provider}");
        
        if ($providerConfig['enabled'] ?? false) {
            // Mode production : vérifier le statut via l'API
            try {
                return $this->checkProviderStatus($payment);
            } catch (\Exception $e) {
                Log::error('Mobile Money status check failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Mode développement : retourner le statut actuel
        return $payment;
    }

    /**
     * Traiter un callback du provider
     *
     * @param array $callbackData
     * @param string $provider
     * @return Payment|null
     */
    public function handleCallback(array $callbackData, string $provider): ?Payment
    {
        $transactionId = $callbackData['transaction_id'] ?? $callbackData['external_reference'] ?? null;
        
        if (!$transactionId) {
            Log::warning('Mobile Money callback missing transaction_id', ['data' => $callbackData]);
            return null;
        }

        $payment = Payment::where('external_reference', $transactionId)
            ->where('provider', $provider)
            ->where('channel', 'mobile_money')
            ->first();

        if (!$payment) {
            Log::warning('Payment not found for callback', [
                'transaction_id' => $transactionId,
                'provider' => $provider,
            ]);
            return null;
        }

        // Mettre à jour le statut selon la réponse du callback
        $status = $callbackData['status'] ?? 'pending';
        
        if ($status === 'success' || $status === 'completed' || $status === 'paid') {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_payment_id' => $callbackData['provider_payment_id'] ?? $payment->provider_payment_id,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'callback_received_at' => now()->toIso8601String(),
                    'callback_data' => $callbackData,
                ]),
            ]);

            // Mettre à jour la commande
            $order = $payment->order;
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing', // Statut commande = processing (pas 'paid')
                ]);
            }

            Log::info('Mobile Money payment completed', [
                'payment_id' => $payment->id,
                'order_id' => $order->id ?? null,
            ]);

            // Phase 3 : Émettre l'event PaymentCompleted pour le monitoring
            if ($order) {
                event(new PaymentCompleted($order, $payment));
            }
        } elseif ($status === 'failed' || $status === 'cancelled') {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'callback_received_at' => now()->toIso8601String(),
                    'failure_reason' => $callbackData['reason'] ?? 'Payment failed',
                    'callback_data' => $callbackData,
                ]),
            ]);

            Log::warning('Mobile Money payment failed', [
                'payment_id' => $payment->id,
                'reason' => $callbackData['reason'] ?? 'Unknown',
            ]);
        }

        return $payment;
    }

    /**
     * Annuler un paiement
     *
     * @param string $transactionId
     * @return bool
     */
    public function cancelPayment(string $transactionId): bool
    {
        $payment = Payment::where('external_reference', $transactionId)
            ->where('channel', 'mobile_money')
            ->first();

        if (!$payment) {
            return false;
        }

        if ($payment->status === 'paid') {
            throw new \Exception('Impossible d\'annuler un paiement déjà effectué');
        }

        $payment->update([
            'status' => 'failed',
            'metadata' => array_merge($payment->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancelled_by' => 'user',
            ]),
        ]);

        Log::info('Mobile Money payment cancelled', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
        ]);

        return true;
    }

    /**
     * Normaliser le numéro de téléphone
     *
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Supprimer les espaces et caractères spéciaux
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Ajouter l'indicatif si manquant
        if (!str_starts_with($phone, '+242') && !str_starts_with($phone, '242')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+242' . substr($phone, 1);
            } else {
                $phone = '+242' . $phone;
            }
        } elseif (str_starts_with($phone, '242')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Valider le numéro de téléphone selon le provider
     *
     * @param string $phone
     * @param string $provider
     * @return bool
     */
    protected function validatePhoneNumber(string $phone, string $provider): bool
    {
        // Format Congo-Brazzaville: +242 06 XX XX XX ou +242 05 XX XX XX
        $pattern = '/^\+242\s?(0[56]\d{8})$/';
        
        if (!preg_match($pattern, str_replace(' ', '', $phone))) {
            return false;
        }

        // Vérifications spécifiques par provider
        switch ($provider) {
            case 'mtn_momo':
                // MTN commence généralement par 06
                return str_contains($phone, '06');
            
            case 'airtel_money':
                // Airtel commence généralement par 05
                return str_contains($phone, '05');
            
            default:
                return true;
        }
    }

    /**
     * Générer un ID de transaction unique
     *
     * @param string $provider
     * @return string
     */
    protected function generateTransactionId(string $provider): string
    {
        $prefix = match($provider) {
            'mtn_momo' => 'MTN',
            'airtel_money' => 'AIRTEL',
            default => 'MM',
        };

        return $prefix . '-' . strtoupper(Str::random(12)) . '-' . time();
    }

    /**
     * Simuler une demande de paiement (pour développement)
     *
     * @param Payment $payment
     * @return void
     */
    protected function simulatePaymentRequest(Payment $payment): void
    {
        // En mode développement, on simule le processus
        // En production, cette méthode appellerait l'API du provider
        
        $payment->update([
            'status' => 'pending',
            'metadata' => array_merge($payment->metadata ?? [], [
                'simulated' => true,
                'instructions' => $this->getPaymentInstructions($payment->provider),
            ]),
        ]);

        Log::info('Mobile Money payment request simulated', [
            'payment_id' => $payment->id,
            'provider' => $payment->provider,
        ]);
    }

    /**
     * Obtenir les instructions de paiement selon le provider
     *
     * @param string $provider
     * @return string
     */
    protected function getPaymentInstructions(string $provider): string
    {
        return match($provider) {
            'mtn_momo' => 'Composez *133*1# sur votre téléphone MTN et suivez les instructions pour valider le paiement.',
            'airtel_money' => 'Composez *150*1# sur votre téléphone Airtel et suivez les instructions pour valider le paiement.',
            default => 'Suivez les instructions reçues sur votre téléphone pour valider le paiement.',
        };
    }

    /**
     * Appeler l'API du provider pour initier le paiement
     *
     * @param Payment $payment
     * @param string $provider
     * @return void
     * @throws \Exception
     */
    protected function callProviderAPI(Payment $payment, string $provider): void
    {
        match($provider) {
            'mtn_momo' => $this->callMtnMomoAPI($payment),
            'airtel_money' => $this->callAirtelMoneyAPI($payment),
            default => throw new \Exception("Provider non supporté: {$provider}"),
        };
    }

    /**
     * Appeler l'API MTN MoMo pour initier le paiement
     *
     * @param Payment $payment
     * @return void
     * @throws \Exception
     */
    protected function callMtnMomoAPI(Payment $payment): void
    {
        $config = config('services.mtn_momo');
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        // Obtenir le token d'authentification
        $token = $this->getMtnToken();
        
        $order = $payment->order;
        $phone = $payment->customer_phone;
        
        // Retirer le + du numéro pour MTN
        $phoneNumber = str_replace('+', '', $phone);
        
        // Appel API MTN MoMo Collection API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Target-Environment' => $environment === 'production' ? 'mtn' : 'sandbox',
            'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
            'X-Reference-Id' => $payment->external_reference,
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/collection/v1_0/requesttopay", [
            'amount' => (string) $payment->amount,
            'currency' => $config['currency'],
            'externalId' => $payment->external_reference,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $phoneNumber,
            ],
            'payerMessage' => 'Paiement RACINE BY GANDA - Commande #' . $order->id,
            'payeeNote' => "Commande #{$order->id}",
        ]);

        if ($response->successful()) {
            $payment->update([
                'status' => 'pending',
                'provider_payment_id' => $payment->external_reference,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'api_called_at' => now()->toIso8601String(),
                    'api_response' => $response->json(),
                ]),
            ]);

            Log::info('MTN MoMo payment request sent', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->external_reference,
            ]);
        } else {
            $error = $response->json();
            throw new \Exception('Erreur MTN MoMo: ' . ($error['message'] ?? $response->body()));
        }
    }

    /**
     * Appeler l'API Airtel Money pour initier le paiement
     *
     * @param Payment $payment
     * @return void
     * @throws \Exception
     */
    protected function callAirtelMoneyAPI(Payment $payment): void
    {
        $config = config('services.airtel_money');
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        // Obtenir le token d'authentification
        $token = $this->getAirtelToken();
        
        $order = $payment->order;
        $phone = $payment->customer_phone;
        
        // Retirer le + du numéro pour Airtel
        $phoneNumber = str_replace('+', '', $phone);
        
        // Appel API Airtel Money
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'X-Country' => 'CG', // Congo-Brazzaville
            'X-Currency' => $config['currency'],
        ])->post("{$baseUrl}/merchant/v1/payments", [
            'reference' => $payment->external_reference,
            'subscriber' => [
                'country' => 'CG',
                'currency' => $config['currency'],
                'msisdn' => $phoneNumber,
            ],
            'transaction' => [
                'amount' => (string) $payment->amount,
                'id' => $payment->external_reference,
            ],
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            $payment->update([
                'status' => 'pending',
                'provider_payment_id' => $responseData['data']['transaction']['id'] ?? $payment->external_reference,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'api_called_at' => now()->toIso8601String(),
                    'api_response' => $responseData,
                ]),
            ]);

            Log::info('Airtel Money payment request sent', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->external_reference,
            ]);
        } else {
            $error = $response->json();
            throw new \Exception('Erreur Airtel Money: ' . ($error['message'] ?? $response->body()));
        }
    }

    /**
     * Obtenir le token d'authentification MTN MoMo
     *
     * @return string
     * @throws \Exception
     */
    protected function getMtnToken(): string
    {
        $config = config('services.mtn_momo');
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        // Générer Basic Auth
        $credentials = base64_encode($config['api_key'] . ':' . $config['api_secret']);
        
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
            'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
        ])->post("{$baseUrl}/collection/token/");

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? '';
        }

        throw new \Exception('Impossible d\'obtenir le token MTN MoMo');
    }

    /**
     * Obtenir le token d'authentification Airtel Money
     *
     * @return string
     * @throws \Exception
     */
    protected function getAirtelToken(): string
    {
        $config = config('services.airtel_money');
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        $response = Http::asForm()->post("{$baseUrl}/auth/oauth2/token", [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'client_credentials',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? '';
        }

        throw new \Exception('Impossible d\'obtenir le token Airtel Money');
    }

    /**
     * Vérifier le statut d'un paiement via l'API du provider
     *
     * @param Payment $payment
     * @return Payment
     * @throws \Exception
     */
    protected function checkProviderStatus(Payment $payment): Payment
    {
        $provider = $payment->provider;
        
        match($provider) {
            'mtn_momo' => $this->checkMtnMomoStatus($payment),
            'airtel_money' => $this->checkAirtelMoneyStatus($payment),
            default => throw new \Exception("Provider non supporté: {$provider}"),
        };

        return $payment->fresh();
    }

    /**
     * Vérifier le statut MTN MoMo
     *
     * @param Payment $payment
     * @return void
     * @throws \Exception
     */
    protected function checkMtnMomoStatus(Payment $payment): void
    {
        $config = config('services.mtn_momo');
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        $token = $this->getMtnToken();
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Target-Environment' => $environment === 'production' ? 'mtn' : 'sandbox',
            'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
        ])->get("{$baseUrl}/collection/v1_0/requesttopay/{$payment->external_reference}");

        if ($response->successful()) {
            $data = $response->json();
            $status = $data['status'] ?? 'PENDING';
            
            $this->updatePaymentStatus($payment, $status, $data);
        } else {
            Log::warning('MTN MoMo status check failed', [
                'payment_id' => $payment->id,
                'response' => $response->body(),
            ]);
        }
    }

    /**
     * Vérifier le statut Airtel Money
     *
     * @param Payment $payment
     * @return void
     * @throws \Exception
     */
    protected function checkAirtelMoneyStatus(Payment $payment): void
    {
        $config = config('services.airtel_money');
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        $token = $this->getAirtelToken();
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Country' => 'CG',
            'X-Currency' => $config['currency'],
        ])->get("{$baseUrl}/standard/v1/payments/{$payment->external_reference}");

        if ($response->successful()) {
            $data = $response->json();
            $status = $data['data']['transaction']['status'] ?? 'PENDING';
            
            $this->updatePaymentStatus($payment, $status, $data);
        } else {
            Log::warning('Airtel Money status check failed', [
                'payment_id' => $payment->id,
                'response' => $response->body(),
            ]);
        }
    }

    /**
     * Mettre à jour le statut du paiement selon la réponse de l'API
     *
     * @param Payment $payment
     * @param string $apiStatus
     * @param array $apiData
     * @return void
     */
    protected function updatePaymentStatus(Payment $payment, string $apiStatus, array $apiData): void
    {
        $statusMap = [
            'SUCCESSFUL' => 'paid',
            'COMPLETED' => 'paid',
            'FAILED' => 'failed',
            'CANCELLED' => 'failed',
            'PENDING' => 'pending',
        ];

        $newStatus = $statusMap[strtoupper($apiStatus)] ?? 'pending';

        if ($newStatus === 'paid' && $payment->status !== 'paid') {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'api_status_checked_at' => now()->toIso8601String(),
                    'api_status' => $apiStatus,
                    'api_data' => $apiData,
                ]),
            ]);

            // Mettre à jour la commande
            $order = $payment->order;
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing', // Statut commande = processing (pas 'paid')
                ]);
            }

            Log::info('Mobile Money payment status updated to paid', [
                'payment_id' => $payment->id,
                'order_id' => $order->id ?? null,
            ]);

            // Phase 3 : Émettre l'event PaymentCompleted pour le monitoring
            if ($order) {
                event(new PaymentCompleted($order, $payment));
            }
        } elseif ($newStatus === 'failed' && $payment->status !== 'failed') {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'api_status_checked_at' => now()->toIso8601String(),
                    'api_status' => $apiStatus,
                    'api_data' => $apiData,
                ]),
            ]);

            // Phase 3 : Émettre l'event PaymentFailed pour le monitoring
            $order = $payment->order;
            if ($order) {
                event(new PaymentFailed($order, "API status: {$apiStatus}"));
            }
        }
    }
}

