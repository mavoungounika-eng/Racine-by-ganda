<?php

namespace App\Listeners;

use App\Events\StockLowAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleStockLowAlert
{
    /**
     * Handle the StockLowAlert event.
     */
    public function handle(StockLowAlert $event): void
    {
        try {
            // ── Throttle : 1 alerte max par produit par intervalle ───────────
            $cacheKey = "stock_alert:{$event->product_id}";
            $ttlSeconds = (int) config('erp.stock_alert_throttle_minutes', 60) * 60;

            if (Cache::has($cacheKey)) {
                Log::channel('erp_stock')->info("StockLowAlert throttled for product #{$event->product_id}");
                return;
            }

            Cache::put($cacheKey, 1, $ttlSeconds);

            // ── Notify admins via DB notification ────────────────────────────
            $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

            if ($admins->isEmpty()) {
                Log::channel('erp_stock')->warning('StockLowAlert: no admin users found');
                return;
            }

            $subject = "⚠ Stock bas — {$event->product_name}";
            $message =
                "Le stock du produit \"{$event->product_name}\" (ID: {$event->product_id}) "
                . "est passé à {$event->current_stock} unité(s), "
                . "en dessous du seuil de {$event->threshold}. "
                . "Source : {$event->source}.";

            // Email aux admins
            foreach ($admins as $admin) {
                try {
                    Mail::raw($message, function ($mail) use ($admin, $subject) {
                        $mail->to($admin->email)->subject($subject);
                    });
                } catch (\Throwable $e) {
                    Log::channel('erp_stock')->error("Failed to send stock alert email to {$admin->email}: " . $e->getMessage());
                }
            }

            // DB notification via NotificationService (si disponible)
            if (class_exists(\App\Services\NotificationService::class)) {
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    foreach ($admins as $admin) {
                        $notificationService->warning(
                            $admin->id,
                            $subject,
                            $message,
                        );
                    }
                } catch (\Throwable $e) {
                    Log::channel('erp_stock')->error('HandleStockLowAlert: NotificationService failed: ' . $e->getMessage());
                }
            }

            Log::channel('erp_stock')->info('StockLowAlert dispatched', [
                'product_id'    => $event->product_id,
                'product_name'  => $event->product_name,
                'current_stock' => $event->current_stock,
                'threshold'     => $event->threshold,
                'source'        => $event->source,
            ]);
        } catch (\Throwable $e) {
            // Never block a sale because of a notification failure
            Log::channel('erp_stock')->error('HandleStockLowAlert failed: ' . $e->getMessage());
        }
    }
}
