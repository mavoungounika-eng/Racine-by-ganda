<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\MobileMoneyPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileMoneyPaymentController extends Controller
{
    protected $mobileMoneyService;

    public function __construct(MobileMoneyPaymentService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }

    /**
     * Afficher le formulaire de paiement Mobile Money
     */
    public function form(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.mobile-money.success', $order);
        }

        return view('frontend.checkout.mobile-money-form', [
            'order' => $order,
        ]);
    }

    /**
     * Initier un paiement Mobile Money
     * 
     * R6 : Rate limiting et limite de tentatives
     * - Rate limiting : 5 tentatives par minute (middleware throttle)
     * - Limite par commande : maximum 3 tentatives par commande
     */
    public function pay(Request $request, Order $order)
    {
        // Protection contre double paiement
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.mobile-money.success', $order)
                ->with('info', 'Cette commande est déjà payée.');
        }

        // R6 : Vérifier le nombre de tentatives pour cette commande
        $maxAttempts = 3;
        $attemptsCount = Payment::where('order_id', $order->id)
            ->where('channel', 'mobile_money')
            ->whereIn('status', ['initiated', 'pending'])
            ->count();

        if ($attemptsCount >= $maxAttempts) {
            Log::warning('Mobile Money payment: too many attempts', [
                'order_id' => $order->id,
                'attempts' => $attemptsCount,
                'max_attempts' => $maxAttempts,
            ]);

            return back()->with('error', 'Vous avez atteint le nombre maximum de tentatives (' . $maxAttempts . ') pour cette commande. Veuillez contacter le support.');
        }

        $request->validate([
            'phone' => 'required|string|min:9|max:15',
            'provider' => 'required|in:mtn_momo,airtel_money',
        ]);

        try {
            $payment = $this->mobileMoneyService->initiatePayment(
                $order,
                $request->phone,
                $request->provider
            );

            return redirect()->route('checkout.mobile-money.pending', [
                'order' => $order->id,
                'payment' => $payment->id,
            ])->with('success', 'Demande de paiement envoyée. Vérifiez votre téléphone.');
        } catch (\Exception $e) {
            Log::error('Mobile Money payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Page d'attente de confirmation
     */
    public function pending(Request $request, Order $order)
    {
        $paymentId = $request->query('payment');
        $payment = $paymentId ? Payment::find($paymentId) : $order->payments()->where('channel', 'mobile_money')->latest()->first();

        if (!$payment) {
            return redirect()->route('checkout')->with('error', 'Paiement introuvable.');
        }

        return view('frontend.checkout.mobile-money-pending', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }

    /**
     * Vérifier le statut du paiement (AJAX)
     */
    public function checkStatus(Request $request, Order $order)
    {
        $paymentId = $request->query('payment');
        $payment = $paymentId ? Payment::find($paymentId) : $order->payments()->where('channel', 'mobile_money')->latest()->first();

        if (!$payment) {
            return response()->json(['error' => 'Paiement introuvable'], 404);
        }

        // Vérifier le statut via le service
        $updatedPayment = $this->mobileMoneyService->checkPaymentStatus($payment->external_reference);

        return response()->json([
            'status' => $updatedPayment->status,
            'paid' => $updatedPayment->status === 'paid',
            'failed' => $updatedPayment->status === 'failed',
        ]);
    }

    /**
     * Page de succès
     */
    public function success(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        $payment = $order->payments()->where('channel', 'mobile_money')->where('status', 'paid')->latest()->first();

        if (!$payment) {
            return redirect()->route('checkout')->with('error', 'Paiement introuvable.');
        }

        // S'assurer que la commande est bien marquée comme payée
        // (au cas où le callback n'aurait pas fonctionné)
        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);
        }

        return view('frontend.checkout.mobile-money-success', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }

    /**
     * Page d'annulation
     */
    public function cancel(Order $order)
    {
        return view('frontend.checkout.mobile-money-cancel', [
            'order' => $order,
        ]);
    }

    /**
     * Callback du provider (webhook)
     */
    public function callback(Request $request, string $provider)
    {
        // Valider le provider
        if (!in_array($provider, ['mtn_momo', 'airtel_money'])) {
            Log::warning('Invalid Mobile Money provider in callback', ['provider' => $provider]);
            return response()->json(['error' => 'Provider invalide'], 400);
        }

        try {
            // Vérifier la signature du webhook en production
            if (!$this->verifyWebhookSignature($request, $provider)) {
                Log::warning('Invalid webhook signature', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Signature invalide'], 401);
            }

            $callbackData = $request->all();
            
            Log::info('Mobile Money callback received', [
                'provider' => $provider,
                'data' => $callbackData,
            ]);

            $payment = $this->mobileMoneyService->handleCallback($callbackData, $provider);

            if (!$payment) {
                return response()->json(['error' => 'Paiement introuvable'], 404);
            }

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Money callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Erreur lors du traitement du callback'], 500);
        }
    }

    /**
     * Vérifier la signature du webhook
     *
     * @param Request $request
     * @param string $provider
     * @return bool
     */
    protected function verifyWebhookSignature(Request $request, string $provider): bool
    {
        $config = config("services.{$provider}");
        $webhookSecret = $config['webhook_secret'] ?? null;

        // En mode développement ou si pas de secret configuré, accepter
        if (app()->environment('local') || !$webhookSecret) {
            return true;
        }

        // Récupérer la signature depuis les headers
        $signature = $request->header('X-Signature') 
                  ?? $request->header('X-Callback-Signature')
                  ?? $request->header('Authorization');

        if (!$signature) {
            return false;
        }

        // Calculer la signature attendue
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Pour MTN MoMo, la signature peut être dans Authorization: Bearer
        if (str_starts_with($signature, 'Bearer ')) {
            $signature = substr($signature, 7);
        }

        // Comparer les signatures de manière sécurisée
        return hash_equals($expectedSignature, $signature);
    }
}

