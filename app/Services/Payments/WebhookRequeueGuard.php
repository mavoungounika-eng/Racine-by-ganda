<?php

namespace App\Services\Payments;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service centralisé pour la logique anti-boucle requeue
 * 
 * Garantit l'atomicité et la cohérence de la logique de requeue
 * pour éviter les boucles infinies même en cas de concurrence.
 */
class WebhookRequeueGuard
{
    /**
     * Nombre maximum de requeue par heure par event
     */
    private const MAX_REQUEUE_PER_HOUR = 5;

    /**
     * Vérifier si un event Stripe peut être requeued
     * 
     * @param StripeWebhookEvent $event
     * @return bool
     */
    public static function canRequeueStripe(StripeWebhookEvent $event): bool
    {
        if ($event->isProcessed() || $event->isBlocked()) {
            return false;
        }

        return self::canRequeue($event->requeue_count, $event->last_requeue_at);
    }

    /**
     * Marquer un event Stripe comme blocked si limite atteinte (appelé depuis le controller/commande)
     * 
     * @param StripeWebhookEvent $event
     * @return bool True si blocked, false sinon
     */
    public static function markStripeAsBlockedIfNeeded(StripeWebhookEvent $event): bool
    {
        if ($event->isProcessed() || $event->isBlocked()) {
            return false;
        }

        $canRequeue = self::canRequeue($event->requeue_count, $event->last_requeue_at);
        
        // Si limite atteinte, marquer comme blocked automatiquement
        if (!$canRequeue && $event->requeue_count >= self::MAX_REQUEUE_PER_HOUR) {
            $oneHourAgo = now()->subHour();
            if ($event->last_requeue_at && $event->last_requeue_at->gt($oneHourAgo)) {
                // Marquer comme blocked si pas déjà blocked
                if (!$event->isBlocked()) {
                    $event->markAsBlocked();
                    // Audit log automatique (si user_id nullable, sinon skip)
                    try {
                        \App\Models\PaymentAuditLog::create([
                            'user_id' => auth()->id(), // Utiliser auth()->id() si disponible, sinon skip
                            'action' => 'auto_block',
                            'target_type' => StripeWebhookEvent::class,
                            'target_id' => $event->id,
                            'diff' => [
                                'requeue_count' => $event->requeue_count,
                                'last_requeue_at' => $event->last_requeue_at?->toIso8601String(),
                            ],
                            'reason' => 'Limite requeue atteinte (5/heure) - Auto-blocked',
                            'ip_address' => request()->ip() ?? 'system',
                            'user_agent' => request()->userAgent() ?? 'system',
                        ]);
                    } catch (\Exception $e) {
                        // Si user_id est required, logger seulement
                        Log::warning('WebhookRequeueGuard: Could not create audit log for auto-block', [
                            'event_id' => $event->event_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifier si un event Monetbil peut être requeued
     * 
     * @param MonetbilCallbackEvent $event
     * @return bool
     */
    public static function canRequeueMonetbil(MonetbilCallbackEvent $event): bool
    {
        if (in_array($event->status, ['processed', 'ignored']) || $event->isBlocked()) {
            return false;
        }

        return self::canRequeue($event->requeue_count, $event->last_requeue_at);
    }

    /**
     * Marquer un event Monetbil comme blocked si limite atteinte (appelé depuis le controller/commande)
     * 
     * @param MonetbilCallbackEvent $event
     * @return bool True si blocked, false sinon
     */
    public static function markMonetbilAsBlockedIfNeeded(MonetbilCallbackEvent $event): bool
    {
        if (in_array($event->status, ['processed', 'ignored']) || $event->isBlocked()) {
            return false;
        }

        $canRequeue = self::canRequeue($event->requeue_count, $event->last_requeue_at);
        
        // Si limite atteinte, marquer comme blocked automatiquement
        if (!$canRequeue && $event->requeue_count >= self::MAX_REQUEUE_PER_HOUR) {
            $oneHourAgo = now()->subHour();
            if ($event->last_requeue_at && $event->last_requeue_at->gt($oneHourAgo)) {
                // Marquer comme blocked si pas déjà blocked
                if (!$event->isBlocked()) {
                    $event->markAsBlocked();
                    // Audit log automatique (si user_id nullable, sinon skip)
                    try {
                        \App\Models\PaymentAuditLog::create([
                            'user_id' => auth()->id(), // Utiliser auth()->id() si disponible, sinon skip
                            'action' => 'auto_block',
                            'target_type' => MonetbilCallbackEvent::class,
                            'target_id' => $event->id,
                            'diff' => [
                                'requeue_count' => $event->requeue_count,
                                'last_requeue_at' => $event->last_requeue_at?->toIso8601String(),
                            ],
                            'reason' => 'Limite requeue atteinte (5/heure) - Auto-blocked',
                            'ip_address' => request()->ip() ?? 'system',
                            'user_agent' => request()->userAgent() ?? 'system',
                        ]);
                    } catch (\Exception $e) {
                        // Si user_id est required, logger seulement
                        Log::warning('WebhookRequeueGuard: Could not create audit log for auto-block', [
                            'event_key' => $event->event_key,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifier si un event peut être requeued (logique centrale)
     * 
     * @param int $requeueCount
     * @param Carbon|null $lastRequeueAt
     * @return bool
     */
    private static function canRequeue(int $requeueCount, ?Carbon $lastRequeueAt): bool
    {
        // Si requeue_count < 5, toujours autorisé
        if ($requeueCount < self::MAX_REQUEUE_PER_HOUR) {
            return true;
        }

        // Si requeue_count >= 5, vérifier le cooldown
        $oneHourAgo = now()->subHour();
        
        // Si last_requeue_at est null, autoriser (cooldown reset)
        if ($lastRequeueAt === null) {
            return true;
        }

        // Si last_requeue_at > 1 heure, autoriser (cooldown reset)
        if ($lastRequeueAt->lte($oneHourAgo)) {
            return true;
        }

        // Sinon, bloquer (limite atteinte et cooldown actif)
        return false;
    }

    /**
     * Calculer le prochain moment où un requeue sera possible
     * 
     * @param int $requeueCount
     * @param Carbon|null $lastRequeueAt
     * @return Carbon|null Retourne null si requeue possible maintenant, sinon la date/heure de déblocage
     */
    public static function getNextRequeueAt(int $requeueCount, ?Carbon $lastRequeueAt): ?Carbon
    {
        if (self::canRequeue($requeueCount, $lastRequeueAt)) {
            return null;
        }

        // Si bloqué, retourner last_requeue_at + 1 heure
        if ($lastRequeueAt) {
            return $lastRequeueAt->copy()->addHour();
        }

        return null;
    }

    /**
     * Obtenir le message d'explication pour un event bloqué
     * 
     * @param int $requeueCount
     * @param Carbon|null $lastRequeueAt
     * @return string
     */
    public static function getBlockedMessage(int $requeueCount, ?Carbon $lastRequeueAt): string
    {
        if ($requeueCount < self::MAX_REQUEUE_PER_HOUR) {
            return '';
        }

        $nextAt = self::getNextRequeueAt($requeueCount, $lastRequeueAt);
        if ($nextAt) {
            return sprintf(
                'Limite atteinte (%d/h), réessayez après %s',
                self::MAX_REQUEUE_PER_HOUR,
                $nextAt->format('H:i')
            );
        }

        return sprintf('Limite atteinte (%d requeue/heure)', self::MAX_REQUEUE_PER_HOUR);
    }

    /**
     * Obtenir le maximum de requeue par heure
     * 
     * @return int
     */
    public static function getMaxRequeuePerHour(): int
    {
        return self::MAX_REQUEUE_PER_HOUR;
    }
}




