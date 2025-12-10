<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour nettoyer les paiements Mobile Money en attente trop anciens
 * 
 * R4 : Timeout côté serveur pour paiements Mobile Money
 * - Marque les paiements pending > 30 minutes comme failed
 * - Nettoie la base de données des paiements abandonnés
 */
class CleanupPendingMobileMoneyPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout en minutes pour considérer un paiement comme abandonné
     */
    protected const TIMEOUT_MINUTES = 30;

    /**
     * Exécuter le job
     */
    public function handle(): void
    {
        $timeoutThreshold = now()->subMinutes(self::TIMEOUT_MINUTES);

        // Récupérer les paiements Mobile Money en attente depuis plus de 30 minutes
        $pendingPayments = Payment::where('channel', 'mobile_money')
            ->where('status', 'pending')
            ->where('created_at', '<', $timeoutThreshold)
            ->get();

        $count = 0;

        foreach ($pendingPayments as $payment) {
            try {
                // Marquer le paiement comme failed
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'timeout_at' => now()->toIso8601String(),
                        'timeout_reason' => 'Paiement abandonné après ' . self::TIMEOUT_MINUTES . ' minutes',
                    ]),
                ]);

                // Mettre à jour la commande si nécessaire
                $order = $payment->order;
                if ($order && $order->payment_status === 'pending') {
                    // Ne pas changer le payment_status de la commande ici
                    // car l'utilisateur peut vouloir réessayer avec un autre paiement
                    Log::info('Mobile Money payment timeout - Order still pending', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                    ]);
                }

                $count++;

                Log::info('Mobile Money payment marked as failed (timeout)', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'created_at' => $payment->created_at,
                    'timeout_threshold' => $timeoutThreshold,
                ]);
            } catch (\Exception $e) {
                Log::error('Error cleaning up pending Mobile Money payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("CleanupPendingMobileMoneyPayments: {$count} paiement(s) marqué(s) comme failed (timeout)");
        }
    }
}

