<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Order $order): string
    {
        $currency = config('services.stripe.currency', 'XOF');
        
        // Création de la session Stripe
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Commande #' . $order->id,
                    ],
                    'unit_amount' => (int) ($order->total_amount * 100), // Stripe attend des centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'metadata' => [
                'order_id' => $order->id,
            ],
            'success_url' => route('payment.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel', ['order' => $order->id]),
        ]);

        // Enregistrement du paiement initial
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_payment_id' => $session->id,
            'status' => 'pending',
            'amount' => $order->total_amount,
            'currency' => $currency,
        ]);

        return $session->url;
    }

    public function markOrderAsPaid(Payment $payment, array $payload): void
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payload' => $payload,
        ]);

        $order = $payment->order;
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing', // Statut commande = processing (pas 'paid')
        ]);
    }
}
