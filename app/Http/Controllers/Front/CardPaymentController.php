<?php

namespace App\Http\Controllers\Front;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\CardPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Contrôleur pour les paiements par carte bancaire via Stripe
 */
class CardPaymentController extends Controller
{
    /**
     * Initier un paiement par carte bancaire
     *
     * @param Request $request
     * @param CardPaymentService $cardPaymentService
     * @return RedirectResponse
     */
    public function pay(Request $request, CardPaymentService $cardPaymentService): RedirectResponse
    {
        // Récupérer order_id depuis la requête ou depuis la session
        $orderId = $request->input('order_id') ?? session('order_id');
        
        if (!$orderId) {
            return redirect()->route('cart.index')
                ->with('error', 'Aucune commande trouvée. Veuillez recommencer.');
        }

        try {
            // Charger la commande
            $order = Order::findOrFail($orderId);

            // Utiliser OrderPolicy pour vérifier l'accès
            $this->authorize('view', $order);

            // Protection contre double paiement
            if ($order->payment_status === 'paid') {
                return redirect()->route('checkout.card.success', $order)
                    ->with('info', 'Cette commande est déjà payée.');
            }

            // Créer la session Stripe Checkout
            $payment = $cardPaymentService->createCheckoutSession($order);

            // Récupérer l'URL de la session Stripe depuis les metadata
            $sessionUrl = $payment->metadata['session_url'] ?? null;

            if (!$sessionUrl) {
                return redirect()->back()
                    ->with('error', 'Erreur lors de la création de la session de paiement.');
            }

            // Rediriger vers Stripe Checkout
            return redirect()->away($sessionUrl);
        } catch (PaymentException $e) {
            return redirect()->back()
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            \Log::error('Erreur paiement carte', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            
            throw new PaymentException(
                $e->getMessage(),
                500,
                'Une erreur est survenue lors de l\'initialisation du paiement. Veuillez réessayer ou contacter le support.'
            );
        }
    }

    /**
     * Page de succès après paiement
     *
     * @param Request $request
     * @param Order $order
     * @return View
     */
    public function success(Request $request, Order $order): View
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        $sessionId = $request->query('session_id');

        return view('frontend.checkout.card-success', [
            'order' => $order,
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * Page d'annulation de paiement
     *
     * @param Request $request
     * @param Order $order
     * @return View
     */
    public function cancel(Request $request, Order $order): View
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        return view('frontend.checkout.card-cancel', [
            'order' => $order,
        ]);
    }

    /**
     * Webhook Stripe pour les notifications de paiement
     *
     * @param Request $request
     * @param CardPaymentService $cardPaymentService
     * @return Response
     */
    /**
     * Webhook Stripe pour les notifications de paiement
     * 
     * RBG-P0-010 : Signature obligatoire en production
     *
     * @param Request $request
     * @param CardPaymentService $cardPaymentService
     * @return Response
     */
    public function webhook(Request $request, CardPaymentService $cardPaymentService): Response
    {
        // Récupérer le payload brut (important pour la vérification de signature)
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $ip = $request->ip();
        $route = $request->fullUrl();
        $userAgent = $request->userAgent();

        try {
            $result = $cardPaymentService->handleWebhook($payload, $signature);
            
            if ($result === null) {
                \Log::warning('Stripe webhook: Processing failed', [
                    'ip' => $ip,
                    'route' => $route,
                    'user_agent' => $userAgent,
                    'reason' => 'processing_failed',
                ]);
                return response()->json(['message' => 'Webhook processing failed'], 400);
            }
            
            \Log::info('Stripe webhook: Successfully processed', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'payment_id' => $result->id ?? null,
            ]);
            
            return response()->json(['status' => 'success'], 200);
        } catch (SignatureVerificationException $e) {
            // RBG-P0-010 : Signature invalide ou manquante → 401
            \Log::error('Stripe webhook: Signature verification failed', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'reason' => 'invalid_signature',
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        } catch (UnexpectedValueException $e) {
            // Payload invalide → 400
            \Log::error('Stripe webhook: Invalid payload', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'reason' => 'invalid_payload',
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (\Throwable $e) {
            // Fallback pour toutes les autres exceptions → 500
            \Log::error('Stripe webhook: Webhook processing failed', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'reason' => 'unexpected_error',
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
}
