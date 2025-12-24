<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CreatorStripeAccount;
use App\Services\Payments\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Contrôleur pour les webhooks Stripe Connect.
 * 
 * Ce contrôleur :
 * - Reçoit les webhooks Stripe Connect
 * - Vérifie la signature Stripe
 * - Filtre uniquement les événements utiles
 * - Appelle syncAccountStatus() pour synchroniser les statuts
 * - Log proprement
 * 
 * ⚠️ Ce contrôleur ne fait PAS :
 * - De logique UI
 * - D'appel Stripe inutile
 * - De création d'abonnement
 * - De redirection
 * - De notification
 */
class StripeConnectWebhookController extends Controller
{
    /**
     * Gère les webhooks Stripe Connect.
     * 
     * Événements gérés :
     * - account.updated → syncAccountStatus()
     * - capability.updated → syncAccountStatus()
     * - account.application.deauthorized → Marquer le compte comme désactivé
     * 
     * Tous les autres événements sont ignorés.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Récupérer le payload brut (important pour vérification signature Stripe)
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret') ?? '';
        $isProduction = app()->environment('production');

        // Log initial (safe)
        Log::info('received_stripe_connect_webhook', [
            'signature_header_present' => !empty($signature),
            'ip' => $request->ip(),
        ]);

        // 1. VÉRIFIER LA SIGNATURE STRIPE
        try {
            if ($isProduction && empty($signature)) {
                Log::error('Stripe Connect webhook: Missing signature in production', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Missing signature'], 400);
            }

            if ($isProduction && empty($webhookSecret)) {
                Log::error('Stripe Connect webhook: Webhook secret not configured', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Configuration error'], 500);
            }

            // Vérifier la signature
            if ($signature && $webhookSecret) {
                try {
                    $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
                } catch (SignatureVerificationException $e) {
                    // Si signature invalide en dev, parser quand même
                    if (!$isProduction) {
                        $event = json_decode($payload, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return response()->json(['error' => 'Invalid JSON'], 400);
                        }
                    } else {
                        Log::error('Stripe Connect webhook: Invalid signature', [
                            'ip' => $request->ip(),
                            'error' => $e->getMessage(),
                        ]);
                        return response()->json(['error' => 'Invalid signature'], 400);
                    }
                }
            } else {
                // Dev mode : parser sans vérification
                $event = json_decode($payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json(['error' => 'Invalid JSON'], 400);
                }
            }
        } catch (SignatureVerificationException $e) {
            // En dev, ignorer l'erreur de signature et parser quand même
            if (!$isProduction) {
                $event = json_decode($payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Stripe Connect webhook: Invalid JSON', [
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['error' => 'Invalid JSON'], 400);
                }
            } else {
                Log::error('Stripe Connect webhook: Invalid signature', [
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Stripe Connect webhook: Verification error', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Verification failed'], 400);
        }

        // Normaliser l'événement en array
        $eventArray = is_object($event) ? json_decode(json_encode($event), true) : $event;

        // Extraire event_type et stripe_account_id
        $eventType = $eventArray['type'] ?? null;
        $stripeAccountId = $eventArray['data']['object']['id'] ?? null;

        if (empty($eventType)) {
            Log::warning('Stripe Connect webhook: Missing event type', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Log avec event_type et stripe_account_id
        Log::info('received_stripe_connect_webhook_parsed', [
            'event_type' => $eventType,
            'stripe_account_id' => $stripeAccountId,
            'ip' => $request->ip(),
        ]);

        // 2. FILTRER ET TRAITER LES ÉVÉNEMENTS
        try {
            $stripeConnectService = app(StripeConnectService::class);

            switch ($eventType) {
                case 'account.updated':
                case 'capability.updated':
                    // Synchroniser le statut du compte
                    if (empty($stripeAccountId)) {
                        Log::warning('Stripe Connect webhook: Missing stripe_account_id', [
                            'event_type' => $eventType,
                            'ip' => $request->ip(),
                        ]);
                        return response()->json(['error' => 'Missing account ID'], 400);
                    }

                    try {
                        $stripeConnectService->syncAccountStatus($stripeAccountId);
                        Log::info('Stripe Connect webhook: Account status synchronized', [
                            'event_type' => $eventType,
                            'stripe_account_id' => $stripeAccountId,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Stripe Connect webhook: Failed to sync account status', [
                            'event_type' => $eventType,
                            'stripe_account_id' => $stripeAccountId,
                            'error' => $e->getMessage(),
                        ]);
                        // Ne pas retourner d'erreur HTTP pour éviter les retries Stripe
                        // Le compte sera synchronisé lors du prochain webhook
                    }
                    break;

                case 'account.application.deauthorized':
                    // Marquer le compte comme désactivé
                    if (empty($stripeAccountId)) {
                        Log::warning('Stripe Connect webhook: Missing stripe_account_id', [
                            'event_type' => $eventType,
                            'ip' => $request->ip(),
                        ]);
                        return response()->json(['error' => 'Missing account ID'], 400);
                    }

                    $creatorAccount = CreatorStripeAccount::where('stripe_account_id', $stripeAccountId)->first();
                    if ($creatorAccount) {
                        $creatorAccount->update([
                            'onboarding_status' => 'failed',
                            'charges_enabled' => false,
                            'payouts_enabled' => false,
                        ]);
                        Log::info('Stripe Connect webhook: Account marked as disabled', [
                            'event_type' => $eventType,
                            'stripe_account_id' => $stripeAccountId,
                            'creator_stripe_account_id' => $creatorAccount->id,
                        ]);
                    } else {
                        Log::warning('Stripe Connect webhook: Account not found for deauthorization', [
                            'event_type' => $eventType,
                            'stripe_account_id' => $stripeAccountId,
                        ]);
                    }
                    break;

                default:
                    // Ignorer tous les autres événements
                    Log::debug('Stripe Connect webhook: Event ignored', [
                        'event_type' => $eventType,
                        'stripe_account_id' => $stripeAccountId,
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Stripe Connect webhook: Processing error', [
                'event_type' => $eventType,
                'stripe_account_id' => $stripeAccountId,
                'error' => $e->getMessage(),
            ]);
            // Ne pas retourner d'erreur HTTP pour éviter les retries Stripe
        }

        // 3. RETOURNER 200 OK
        return response()->json(['status' => 'ok'], 200);
    }
}




