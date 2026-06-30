<?php

namespace App\Jobs;

use App\Mail\AbandonedCartReminderMail;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Détecte les paniers abandonnés et envoie 3 relances :
 *   - Relance 1 : 1 heure après la dernière activité
 *   - Relance 2 : 24 heures après la dernière activité
 *   - Relance 3 : 3 jours (72 heures) après la dernière activité
 *
 * Anti-doublon : aucune relance si l'utilisateur a passé une commande
 * après la dernière modification du panier.
 */
class SendAbandonedCartReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Seuils en minutes
    private const THRESHOLDS = [
        1 => 60,         // 1 heure
        2 => 60 * 24,    // 24 heures
        3 => 60 * 72,    // 3 jours
    ];

    public int $tries = 3;

    public function handle(): void
    {
        $processed = 0;

        Cart::with(['user', 'items.product'])
            ->has('items')
            ->where('reminder_count', '<', 3)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->lazyById(100)
            ->each(function (Cart $cart) use (&$processed) {
                $this->processCart($cart);
                $processed++;
            });

        Log::info("[AbandonedCart] Processed {$processed} carts.");
    }

    private function processCart(Cart $cart): void
    {
        $user = $cart->user;
        if (!$user || !$user->email) {
            return;
        }

        // Prendre la date de dernière activité = dernière modif d'un item
        $lastActivity = $cart->items->max('updated_at') ?? $cart->updated_at;
        if (!$lastActivity) {
            return;
        }

        $minutesSince = now()->diffInMinutes($lastActivity);

        // Déterminer quelle relance envoyer
        $nextReminder = $cart->reminder_count + 1;
        if (!isset(self::THRESHOLDS[$nextReminder])) {
            return;
        }

        if ($minutesSince < self::THRESHOLDS[$nextReminder]) {
            return;
        }

        // Anti-doublon : commande passée après la dernière activité du panier
        $hasOrderSince = Order::where('user_id', $user->id)
            ->where('created_at', '>', $lastActivity)
            ->exists();

        if ($hasOrderSince) {
            // Panier plus vraiment abandonné — reset silencieux
            $cart->update(['reminder_count' => 3, 'last_reminder_sent_at' => now()]);
            return;
        }

        try {
            Mail::to($user->email)->queue(
                new AbandonedCartReminderMail($cart, $user, $nextReminder)
            );

            $cart->update([
                'reminder_count'       => $nextReminder,
                'last_reminder_sent_at' => now(),
            ]);

            Log::info("[AbandonedCart] Reminder #{$nextReminder} sent", [
                'user_id'   => $user->id,
                'cart_id'   => $cart->id,
                'items'     => $cart->items->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error("[AbandonedCart] Failed to send reminder", [
                'user_id' => $user->id,
                'cart_id' => $cart->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
