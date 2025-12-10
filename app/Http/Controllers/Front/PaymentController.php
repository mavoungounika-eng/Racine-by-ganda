<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PaymentController extends Controller
{
    protected $stripePaymentService;

    public function __construct(StripePaymentService $stripePaymentService)
    {
        $this->stripePaymentService = $stripePaymentService;
    }

    public function pay(Request $request, Order $order)
    {
        // Sécurité : Vérifier que l'utilisateur est bien le propriétaire de la commande
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('payment.success', $order);
        }

        try {
            $checkoutUrl = $this->stripePaymentService->createCheckoutSession($order);
            return redirect($checkoutUrl);
        } catch (\Exception $e) {
            Log::error('Stripe Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement.');
        }
    }

    public function success(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        return view('checkout.success', compact('order'));
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        return view('checkout.cancel', compact('order'));
    }

    public function webhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Gestion de l'événement
        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            
            // Récupérer le paiement via l'ID de session
            $payment = Payment::where('provider_payment_id', $session->id)->first();

            if ($payment) {
                $this->stripePaymentService->markOrderAsPaid($payment, $session->toArray());
                Log::info('Payment validated for Order #' . $payment->order_id);
            } else {
                Log::warning('Payment not found for session ID: ' . $session->id);
            }
        }

        return response()->json(['status' => 'success']);
    }
}
