<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookMonitorController extends Controller
{
    /**
     * Monitoring webhooks/callbacks (Stripe + Monetbil)
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('payments.view');

        $provider = $request->get('provider', 'all'); // all, stripe, monetbil

        // Stripe events
        $stripeQuery = StripeWebhookEvent::query();
        if ($provider === 'stripe' || $provider === 'all') {
            if ($request->filled('status')) {
                $stripeQuery->where('status', $request->get('status'));
            }
            if ($request->filled('event_type')) {
                $stripeQuery->where('event_type', 'like', '%' . $request->get('event_type') . '%');
            }
            if ($request->filled('date_from')) {
                $stripeQuery->whereDate('created_at', '>=', $request->get('date_from'));
            }
            if ($request->filled('date_to')) {
                $stripeQuery->whereDate('created_at', '<=', $request->get('date_to'));
            }
        }

        // Monetbil events
        $monetbilQuery = MonetbilCallbackEvent::query();
        if ($provider === 'monetbil' || $provider === 'all') {
            if ($request->filled('status')) {
                $monetbilQuery->where('status', $request->get('status'));
            }
            if ($request->filled('event_type')) {
                $monetbilQuery->where('event_type', 'like', '%' . $request->get('event_type') . '%');
            }
            if ($request->filled('date_from')) {
                $monetbilQuery->whereDate('created_at', '>=', $request->get('date_from'));
            }
            if ($request->filled('date_to')) {
                $monetbilQuery->whereDate('created_at', '<=', $request->get('date_to'));
            }
        }

        $stripeEvents = $provider === 'all' || $provider === 'stripe'
            ? $stripeQuery->orderBy('created_at', 'desc')->paginate(15, ['*'], 'stripe_page')->withQueryString()
            : null;

        $monetbilEvents = $provider === 'all' || $provider === 'monetbil'
            ? $monetbilQuery->orderBy('created_at', 'desc')->paginate(15, ['*'], 'monetbil_page')->withQueryString()
            : null;

        // Stats
        $stats = [
            'stripe' => [
                'total' => StripeWebhookEvent::count(),
                'processed' => StripeWebhookEvent::where('status', 'processed')->count(),
                'failed' => StripeWebhookEvent::where('status', 'failed')->count(),
                'received' => StripeWebhookEvent::where('status', 'received')->count(),
            ],
            'monetbil' => [
                'total' => MonetbilCallbackEvent::count(),
                'processed' => MonetbilCallbackEvent::where('status', 'processed')->count(),
                'failed' => MonetbilCallbackEvent::where('status', 'failed')->count(),
                'received' => MonetbilCallbackEvent::where('status', 'received')->count(),
            ],
        ];

        return view('admin.payments.webhooks.index', compact('stripeEvents', 'monetbilEvents', 'stats', 'provider'));
    }

    /**
     * Détail d'un événement Stripe
     *
     * @param StripeWebhookEvent $event
     * @return View
     */
    public function showStripe(StripeWebhookEvent $event): View
    {
        $this->authorize('payments.view');

        return view('admin.payments.webhooks.show-stripe', compact('event'));
    }

    /**
     * Détail d'un événement Monetbil
     *
     * @param MonetbilCallbackEvent $event
     * @return View
     */
    public function showMonetbil(MonetbilCallbackEvent $event): View
    {
        $this->authorize('payments.view');

        return view('admin.payments.webhooks.show-monetbil', compact('event'));
    }
}




