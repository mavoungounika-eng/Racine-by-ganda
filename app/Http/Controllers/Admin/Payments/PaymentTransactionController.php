<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\MonetbilCallbackEvent;
use App\Models\PaymentTransaction;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\CsvExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PaymentTransactionController extends Controller
{
    /**
     * Liste des transactions avec filtres
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('payments.view');

        $query = PaymentTransaction::with('order');

        // Filtre par provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->get('provider'));
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filtre par date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Filtre par montant min/max
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->get('amount_min'));
        }
        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->get('amount_max'));
        }

        // Filtre par order_id
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Filtre par payment_ref
        if ($request->filled('payment_ref')) {
            $query->where('payment_ref', 'like', '%' . $request->get('payment_ref') . '%');
        }

        // Recherche générale
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('payment_ref', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhere('transaction_uuid', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $transactions = $query->paginate(20)->withQueryString();

        // Stats pour les filtres
        $stats = [
            'total' => PaymentTransaction::count(),
            'succeeded' => PaymentTransaction::where('status', 'succeeded')->count(),
            'failed' => PaymentTransaction::where('status', 'failed')->count(),
            'pending' => PaymentTransaction::whereIn('status', ['pending', 'processing'])->count(),
        ];

        return view('admin.payments.transactions.index', compact('transactions', 'stats'));
    }

    /**
     * Détail d'une transaction + timeline events
     *
     * @param PaymentTransaction $transaction
     * @return View
     */
    public function show(PaymentTransaction $transaction): View
    {
        $this->authorize('payments.view');

        // Timeline : événements Stripe associés
        $stripeEvents = StripeWebhookEvent::where('payment_id', $transaction->id)
            ->orWhere('event_id', 'like', '%' . $transaction->transaction_id . '%')
            ->orderBy('created_at', 'desc')
            ->get();

        // Timeline : événements Monetbil associés
        $monetbilEvents = MonetbilCallbackEvent::where('payment_ref', $transaction->payment_ref)
            ->orWhere('transaction_id', $transaction->transaction_id)
            ->orWhere('transaction_uuid', $transaction->transaction_uuid)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fusionner et trier par date
        $timelineEvents = collect()
            ->merge($stripeEvents->map(function ($event) {
                return [
                    'type' => 'stripe',
                    'event' => $event,
                    'created_at' => $event->created_at,
                ];
            }))
            ->merge($monetbilEvents->map(function ($event) {
                return [
                    'type' => 'monetbil',
                    'event' => $event,
                    'created_at' => $event->created_at,
                ];
            }))
            ->sortByDesc('created_at')
            ->values();

        return view('admin.payments.transactions.show', compact('transaction', 'timelineEvents'));
    }

    /**
     * Export CSV des transactions (anti-injection)
     *
     * @param Request $request
     * @return Response
     */
    public function exportCsv(Request $request): Response
    {
        $this->authorize('payments.view');

        $query = PaymentTransaction::with('order');

        // Appliquer les mêmes filtres que index()
        if ($request->filled('provider')) {
            $query->where('provider', $request->get('provider'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $csvService = new CsvExportService();
        $csvContent = $csvService->exportTransactions($transactions);

        $filename = 'transactions_' . now()->format('Y-m-d_His') . '.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}




