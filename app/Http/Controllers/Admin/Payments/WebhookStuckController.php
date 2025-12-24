<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\PaymentAuditLog;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\WebhookRequeueGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WebhookStuckController extends Controller
{
    /**
     * Liste des événements stuck
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('payments.view');

        $provider = $request->get('provider', 'all');
        $status = $request->get('status', 'all');
        $minutes = (int) $request->get('minutes', 10);
        $q = $request->get('q');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $thresholdTime = now()->subMinutes($minutes);

        $results = collect();

        // Stripe events stuck (inclure blocked)
        if ($provider === 'all' || $provider === 'stripe') {
            $stripeQuery = StripeWebhookEvent::whereIn('status', ['received', 'failed', 'blocked'])
                ->where(function ($query) use ($thresholdTime) {
                    $query->whereNull('dispatched_at')
                        ->orWhere(function ($q) use ($thresholdTime) {
                            $q->where('status', 'failed')
                                ->whereNotNull('dispatched_at')
                                ->where('dispatched_at', '<', $thresholdTime);
                        })
                        ->orWhere('status', 'blocked'); // Inclure les blocked
                });

            if ($status !== 'all') {
                $stripeQuery->where('status', $status);
            }

            if ($q) {
                $stripeQuery->where('event_id', 'like', '%' . $q . '%');
            }

            if ($dateFrom) {
                $stripeQuery->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $stripeQuery->whereDate('created_at', '<=', $dateTo);
            }

            $stripeEvents = $stripeQuery->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($event) use ($thresholdTime) {
                    return [
                        'provider' => 'stripe',
                        'id' => $event->id,
                        'event_identifier' => $event->event_id,
                        'event_type' => $event->event_type,
                        'status' => $event->status,
                        'created_at' => $event->created_at,
                        'dispatched_at' => $event->dispatched_at,
                        'processed_at' => $event->processed_at,
                        'failure_hint' => null,
                        'is_stuck_reason' => $this->getStuckReason($event, $thresholdTime),
                        'requeue_count' => $event->requeue_count ?? 0,
                        'last_requeue_at' => $event->last_requeue_at,
                    ];
                });

            $results = $results->concat($stripeEvents);
        }

        // Monetbil events stuck (inclure blocked)
        if ($provider === 'all' || $provider === 'monetbil') {
            $monetbilQuery = MonetbilCallbackEvent::whereIn('status', ['received', 'failed', 'blocked'])
                ->where(function ($query) use ($thresholdTime) {
                    $query->whereNull('dispatched_at')
                        ->orWhere(function ($q) use ($thresholdTime) {
                            $q->where('status', 'failed')
                                ->whereNotNull('dispatched_at')
                                ->where('dispatched_at', '<', $thresholdTime);
                        })
                        ->orWhere('status', 'blocked'); // Inclure les blocked
                });

            if ($status !== 'all') {
                $monetbilQuery->where('status', $status);
            }

            if ($q) {
                $monetbilQuery->where(function ($query) use ($q) {
                    $query->where('payment_ref', 'like', '%' . $q . '%')
                        ->orWhere('transaction_id', 'like', '%' . $q . '%');
                });
            }

            if ($dateFrom) {
                $monetbilQuery->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $monetbilQuery->whereDate('created_at', '<=', $dateTo);
            }

            $monetbilEvents = $monetbilQuery->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($event) use ($thresholdTime) {
                    return [
                        'provider' => 'monetbil',
                        'id' => $event->id,
                        'event_identifier' => $event->event_key,
                        'event_type' => $event->event_type,
                        'status' => $event->status,
                        'created_at' => $event->created_at,
                        'dispatched_at' => $event->dispatched_at,
                        'processed_at' => $event->processed_at,
                        'failure_hint' => $event->error,
                        'is_stuck_reason' => $this->getStuckReason($event, $thresholdTime),
                        'requeue_count' => $event->requeue_count ?? 0,
                        'last_requeue_at' => $event->last_requeue_at,
                    ];
                });

            $results = $results->concat($monetbilEvents);
        }

        // Trier par created_at desc et paginer
        $results = $results->sortByDesc('created_at')->values();
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $items = $results->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $results->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Stats mini
        $stats = $this->calculateStats($thresholdTime);

        return view('admin.payments.webhooks.stuck', compact(
            'paginated',
            'stats',
            'provider',
            'status',
            'minutes',
            'q',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Requeue bulk (sélection ou tous les filtres)
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function requeue(Request $request): RedirectResponse
    {
        $this->authorize('payments.reprocess');

        $validated = $request->validate([
            'reason' => 'required|string|min:5',
            'minutes' => 'required|integer|min:1',
            'provider' => 'nullable|in:all,stripe,monetbil',
            'ids' => 'nullable',
        ]);

        $reason = $validated['reason'];
        $minutes = (int) $validated['minutes'];
        $provider = $validated['provider'] ?? 'all';
        // Parser ids si c'est une string JSON
        $ids = $validated['ids'] ?? [];
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }
        $thresholdTime = now()->subMinutes($minutes);

        $stats = [
            'stripe' => ['scanned' => 0, 'dispatched' => 0, 'skipped' => 0],
            'monetbil' => ['scanned' => 0, 'dispatched' => 0, 'skipped' => 0],
        ];

        // Si ids fournis, requeue seulement ceux-là
        if (!empty($ids)) {
            // Séparer par provider (on suppose que les ids sont fournis avec provider)
            // Pour simplifier, on va itérer sur tous les ids et déterminer le provider
            foreach ($ids as $idData) {
                if (is_array($idData)) {
                    $eventProvider = $idData['provider'] ?? 'stripe';
                    $eventId = $idData['id'] ?? null;
                } else {
                    // Format simple: on doit deviner le provider
                    // Pour l'instant, on essaie Stripe puis Monetbil
                    $eventProvider = 'stripe';
                    $eventId = $idData;
                }

                if (!$eventId) {
                    continue;
                }

                if ($eventProvider === 'stripe' && ($provider === 'all' || $provider === 'stripe')) {
                    $result = $this->requeueStripeEvent($eventId, $thresholdTime, $reason, $request);
                    $stats['stripe']['scanned']++;
                    if ($result) {
                        $stats['stripe']['dispatched']++;
                    } else {
                        $stats['stripe']['skipped']++;
                    }
                } elseif ($eventProvider === 'monetbil' && ($provider === 'all' || $provider === 'monetbil')) {
                    $result = $this->requeueMonetbilEvent($eventId, $thresholdTime, $reason, $request);
                    $stats['monetbil']['scanned']++;
                    if ($result) {
                        $stats['monetbil']['dispatched']++;
                    } else {
                        $stats['monetbil']['skipped']++;
                    }
                }
            }
        } else {
            // Requeue tous les events stuck selon les filtres
            // Utiliser la même logique que index() pour sélectionner
            if ($provider === 'all' || $provider === 'stripe') {
                $stripeEvents = StripeWebhookEvent::whereIn('status', ['received', 'failed'])
                    ->where(function ($query) use ($thresholdTime) {
                        $query->whereNull('dispatched_at')
                            ->orWhere(function ($q) use ($thresholdTime) {
                                $q->where('status', 'failed')
                                    ->whereNotNull('dispatched_at')
                                    ->where('dispatched_at', '<', $thresholdTime);
                            });
                    })
                    ->get();

                foreach ($stripeEvents as $event) {
                    $stats['stripe']['scanned']++;
                    if ($this->requeueStripeEvent($event->id, $thresholdTime, $reason)) {
                        $stats['stripe']['dispatched']++;
                    } else {
                        $stats['stripe']['skipped']++;
                    }
                }
            }

            if ($provider === 'all' || $provider === 'monetbil') {
                $monetbilEvents = MonetbilCallbackEvent::whereIn('status', ['received', 'failed'])
                    ->where(function ($query) use ($thresholdTime) {
                        $query->whereNull('dispatched_at')
                            ->orWhere(function ($q) use ($thresholdTime) {
                                $q->where('status', 'failed')
                                    ->whereNotNull('dispatched_at')
                                    ->where('dispatched_at', '<', $thresholdTime);
                            });
                    })
                    ->get();

                    foreach ($monetbilEvents as $event) {
                    $stats['monetbil']['scanned']++;
                    if ($this->requeueMonetbilEvent($event->id, $thresholdTime, $reason, $request)) {
                        $stats['monetbil']['dispatched']++;
                    } else {
                        $stats['monetbil']['skipped']++;
                    }
                }
            }
        }

        $totalScanned = $stats['stripe']['scanned'] + $stats['monetbil']['scanned'];
        $totalDispatched = $stats['stripe']['dispatched'] + $stats['monetbil']['dispatched'];
        $totalSkipped = $stats['stripe']['skipped'] + $stats['monetbil']['skipped'];

        $message = "Requeue terminé: {$totalScanned} scannés, {$totalDispatched} dispatchés, {$totalSkipped} ignorés";
        return redirect()->back()->with('success', $message);
    }

    /**
     * Requeue un seul événement
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function requeueOne(Request $request)
    {
        $this->authorize('payments.reprocess');

        $validated = $request->validate([
            'provider' => 'required|in:stripe,monetbil',
            'id' => 'required|integer',
            'reason' => 'required|string|min:5',
            'minutes' => 'required|integer|min:1',
        ]);

        $provider = $validated['provider'];
        $eventId = $validated['id'];
        $reason = $validated['reason'];
        $minutes = (int) $validated['minutes'];
        $thresholdTime = now()->subMinutes($minutes);

        $dispatched = false;

        if ($provider === 'stripe') {
            $dispatched = $this->requeueStripeEvent($eventId, $thresholdTime, $reason, $request);
        } elseif ($provider === 'monetbil') {
            $dispatched = $this->requeueMonetbilEvent($eventId, $thresholdTime, $reason, $request);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => $dispatched,
                'message' => $dispatched ? 'Événement requeued avec succès' : 'Événement non requeued (déjà dispatché ou status final)',
            ]);
        }

        return redirect()->back()->with(
            $dispatched ? 'success' : 'info',
            $dispatched ? 'Événement requeued avec succès' : 'Événement non requeued (déjà dispatché ou status final)'
        );
    }

    /**
     * Requeue un événement Stripe (atomic claim)
     *
     * @param int $eventId
     * @param \Carbon\Carbon $thresholdTime
     * @param string $reason
     * @return bool
     */
    private function requeueStripeEvent(int $eventId, $thresholdTime, string $reason, ?Request $request = null): bool
    {
        $event = StripeWebhookEvent::find($eventId);
        if (!$event || $event->isProcessed()) {
            return false;
        }

        // Garde-fou anti-boucle via service centralisé
        if (!WebhookRequeueGuard::canRequeueStripe($event)) {
            // Tenter de marquer comme blocked si limite atteinte
            WebhookRequeueGuard::markStripeAsBlockedIfNeeded($event);
            Log::warning('WebhookStuckController: Requeue limit reached for Stripe event', [
                'event_id' => $event->event_id,
                'requeue_count' => $event->requeue_count,
                'last_requeue_at' => $event->last_requeue_at,
            ]);
            return false;
        }

        $dispatched = false;

        // Atomic claim 1 : dispatched_at IS NULL
        $rowsAffected = DB::table('stripe_webhook_events')
            ->where('id', $eventId)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected === 1) {
            try {
                ProcessStripeWebhookEventJob::dispatch($eventId);
                $dispatched = true;
            } catch (\Exception $e) {
                Log::error('WebhookStuckController: Failed to dispatch Stripe job', [
                    'event_id' => $event->event_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Atomic claim 2 : failed + dispatched_at old
        if (!$dispatched && $event->status === 'failed' && $event->dispatched_at && $event->dispatched_at->lt($thresholdTime)) {
            $rowsAffected = DB::table('stripe_webhook_events')
                ->where('id', $eventId)
                ->where('status', 'failed')
                ->whereNotNull('dispatched_at')
                ->where('dispatched_at', '<', $thresholdTime)
                ->update([
                    'dispatched_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($rowsAffected === 1) {
                try {
                    ProcessStripeWebhookEventJob::dispatch($eventId);
                    $dispatched = true;
                } catch (\Exception $e) {
                    Log::error('WebhookStuckController: Failed to redispatch Stripe job', [
                        'event_id' => $event->event_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($dispatched) {
            // Mettre à jour requeue_count et last_requeue_at (atomic)
            // Utiliser une condition WHERE pour garantir l'atomicité même en concurrence
            DB::table('stripe_webhook_events')
                ->where('id', $eventId)
                ->where(function ($query) {
                    // Permettre l'incrément si requeue_count < 5 OU cooldown expiré
                    $query->where('requeue_count', '<', WebhookRequeueGuard::getMaxRequeuePerHour())
                        ->orWhereNull('last_requeue_at')
                        ->orWhere('last_requeue_at', '<=', now()->subHour());
                })
                ->update([
                    'requeue_count' => DB::raw('requeue_count + 1'),
                    'last_requeue_at' => now(),
                    'updated_at' => now(),
                ]);

            // Audit log
            PaymentAuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'reprocess',
                'target_type' => StripeWebhookEvent::class,
                'target_id' => $eventId,
                'diff' => [
                    'mode' => 'single',
                    'threshold_minutes' => now()->diffInMinutes($thresholdTime),
                    'requeue_count' => $event->requeue_count + 1,
                ],
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $dispatched;
    }

    /**
     * Requeue un événement Monetbil (atomic claim)
     *
     * @param int $eventId
     * @param \Carbon\Carbon $thresholdTime
     * @param string $reason
     * @param Request|null $request
     * @return bool
     */
    private function requeueMonetbilEvent(int $eventId, $thresholdTime, string $reason, ?Request $request = null): bool
    {
        $event = MonetbilCallbackEvent::find($eventId);
        if (!$event || in_array($event->status, ['processed', 'ignored'])) {
            return false;
        }

        // Garde-fou anti-boucle via service centralisé
        if (!WebhookRequeueGuard::canRequeueMonetbil($event)) {
            // Tenter de marquer comme blocked si limite atteinte
            WebhookRequeueGuard::markMonetbilAsBlockedIfNeeded($event);
            Log::warning('WebhookStuckController: Requeue limit reached for Monetbil event', [
                'event_key' => $event->event_key,
                'requeue_count' => $event->requeue_count,
                'last_requeue_at' => $event->last_requeue_at,
            ]);
            return false;
        }

        $dispatched = false;

        // Atomic claim 1 : dispatched_at IS NULL
        $rowsAffected = DB::table('monetbil_callback_events')
            ->where('id', $eventId)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected === 1) {
            try {
                ProcessMonetbilCallbackEventJob::dispatch($eventId);
                $dispatched = true;
            } catch (\Exception $e) {
                Log::error('WebhookStuckController: Failed to dispatch Monetbil job', [
                    'event_key' => $event->event_key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Atomic claim 2 : failed + dispatched_at old
        if (!$dispatched && $event->status === 'failed' && $event->dispatched_at && $event->dispatched_at->lt($thresholdTime)) {
            $rowsAffected = DB::table('monetbil_callback_events')
                ->where('id', $eventId)
                ->where('status', 'failed')
                ->whereNotNull('dispatched_at')
                ->where('dispatched_at', '<', $thresholdTime)
                ->update([
                    'dispatched_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($rowsAffected === 1) {
                try {
                    ProcessMonetbilCallbackEventJob::dispatch($eventId);
                    $dispatched = true;
                } catch (\Exception $e) {
                    Log::error('WebhookStuckController: Failed to redispatch Monetbil job', [
                        'event_key' => $event->event_key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($dispatched) {
            // Mettre à jour requeue_count et last_requeue_at (atomic)
            // Utiliser une condition WHERE pour garantir l'atomicité même en concurrence
            DB::table('monetbil_callback_events')
                ->where('id', $eventId)
                ->where(function ($query) {
                    // Permettre l'incrément si requeue_count < 5 OU cooldown expiré
                    $query->where('requeue_count', '<', WebhookRequeueGuard::getMaxRequeuePerHour())
                        ->orWhereNull('last_requeue_at')
                        ->orWhere('last_requeue_at', '<=', now()->subHour());
                })
                ->update([
                    'requeue_count' => DB::raw('requeue_count + 1'),
                    'last_requeue_at' => now(),
                    'updated_at' => now(),
                ]);

            // Audit log
            PaymentAuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'reprocess',
                'target_type' => MonetbilCallbackEvent::class,
                'target_id' => $eventId,
                'diff' => [
                    'mode' => 'single',
                    'threshold_minutes' => now()->diffInMinutes($thresholdTime),
                    'requeue_count' => $event->requeue_count + 1,
                ],
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $dispatched;
    }

    /**
     * Obtenir la raison du stuck
     *
     * @param mixed $event
     * @param \Carbon\Carbon $thresholdTime
     * @return string
     */
    private function getStuckReason($event, $thresholdTime): string
    {
        if ($event->status === 'blocked') {
            return 'blocked (requeue limit reached)';
        }

        if ($event->dispatched_at === null) {
            return 'dispatched_at NULL';
        }

        if ($event->status === 'failed' && $event->dispatched_at && $event->dispatched_at->lt($thresholdTime)) {
            return 'failed older than threshold';
        }

        return 'unknown';
    }

    /**
     * Calculer les stats mini
     *
     * @param \Carbon\Carbon $thresholdTime
     * @return array
     */
    private function calculateStats($thresholdTime): array
    {
        $stripeNull = StripeWebhookEvent::whereIn('status', ['received', 'failed'])
            ->whereNull('dispatched_at')
            ->count();

        $stripeFailedOld = StripeWebhookEvent::where('status', 'failed')
            ->whereNotNull('dispatched_at')
            ->where('dispatched_at', '<', $thresholdTime)
            ->count();

        $stripeBlocked = StripeWebhookEvent::where('status', 'blocked')->count();

        $monetbilNull = MonetbilCallbackEvent::whereIn('status', ['received', 'failed'])
            ->whereNull('dispatched_at')
            ->count();

        $monetbilFailedOld = MonetbilCallbackEvent::where('status', 'failed')
            ->whereNotNull('dispatched_at')
            ->where('dispatched_at', '<', $thresholdTime)
            ->count();

        $monetbilBlocked = MonetbilCallbackEvent::where('status', 'blocked')->count();

        return [
            'stripe_total' => $stripeNull + $stripeFailedOld + $stripeBlocked,
            'stripe_received' => StripeWebhookEvent::where('status', 'received')
                ->whereNull('dispatched_at')
                ->count(),
            'stripe_failed_old' => $stripeFailedOld,
            'stripe_null_dispatched' => $stripeNull,
            'stripe_blocked' => $stripeBlocked,
            'monetbil_total' => $monetbilNull + $monetbilFailedOld + $monetbilBlocked,
            'monetbil_received' => MonetbilCallbackEvent::where('status', 'received')
                ->whereNull('dispatched_at')
                ->count(),
            'monetbil_failed_old' => $monetbilFailedOld,
            'monetbil_null_dispatched' => $monetbilNull,
            'monetbil_blocked' => $monetbilBlocked,
        ];
    }

    /**
     * Reset requeue window (remet requeue_count à 0 et last_requeue_at à null)
     * 
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function resetRequeueWindow(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('payments.reprocess');

        $validated = $request->validate([
            'provider' => 'required|in:stripe,monetbil',
            'id' => 'required|integer',
            'reason' => 'required|string|min:5',
        ]);

        $provider = $validated['provider'];
        $eventId = (int) $validated['id'];
        $reason = $validated['reason'];

        $success = false;
        $eventIdentifier = null;

        if ($provider === 'stripe') {
            $event = StripeWebhookEvent::find($eventId);
            if ($event && $event->isBlocked()) {
                $eventIdentifier = $event->event_id;
                $event->update([
                    'requeue_count' => 0,
                    'last_requeue_at' => null,
                    'status' => 'received', // Réactiver en received
                ]);

                // Audit log
                PaymentAuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'reset_requeue_window',
                    'target_type' => StripeWebhookEvent::class,
                    'target_id' => $eventId,
                    'diff' => [
                        'requeue_count' => 0,
                        'last_requeue_at' => null,
                        'previous_status' => 'blocked',
                        'new_status' => 'received',
                    ],
                    'reason' => $reason,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                $success = true;
            }
        } elseif ($provider === 'monetbil') {
            $event = MonetbilCallbackEvent::find($eventId);
            if ($event && $event->isBlocked()) {
                $eventIdentifier = $event->event_key;
                $event->update([
                    'requeue_count' => 0,
                    'last_requeue_at' => null,
                    'status' => 'received', // Réactiver en received
                ]);

                // Audit log
                PaymentAuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'reset_requeue_window',
                    'target_type' => MonetbilCallbackEvent::class,
                    'target_id' => $eventId,
                    'diff' => [
                        'requeue_count' => 0,
                        'last_requeue_at' => null,
                        'previous_status' => 'blocked',
                        'new_status' => 'received',
                    ],
                    'reason' => $reason,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                $success = true;
            }
        }

        if ($success) {
            $message = "Requeue window réinitialisé pour {$provider} event {$eventIdentifier}";
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->back()->with('success', $message);
        }

        $error = "Event {$provider} #{$eventId} non trouvé ou non bloqué";
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'error' => $error], 404);
        }
        return redirect()->back()->with('error', $error);
    }
}




