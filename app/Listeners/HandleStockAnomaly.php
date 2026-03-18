<?php

namespace App\Listeners;

use App\Events\StockAnomalyDetected;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleStockAnomaly
{
    /**
     * Handle the StockAnomalyDetected event.
     * No throttle — every anomaly must be reported immediately.
     */
    public function handle(StockAnomalyDetected $event): void
    {
        try {
            Log::channel('erp_stock')->warning('Stock anomaly detected', [
                'product_id'     => $event->product_id,
                'order_id'       => $event->order_id,
                'requested_qty'  => $event->requested_qty,
                'available_stock' => $event->available_stock,
                'detected_at'    => $event->detected_at,
            ]);

            $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

            $subject = '🚨 ANOMALIE STOCK CRITIQUE';
            $body = sprintf(
                "ANOMALIE DE STOCK DÉTECTÉE\n\n"
                . "Produit ID : %d\n"
                . "Commande ID : %s\n"
                . "Quantité demandée : %d\n"
                . "Stock disponible au moment de la vérification : %d\n"
                . "Détecté le : %s\n\n"
                . "Action requise : vérifier manuellement le stock et corriger si nécessaire.",
                $event->product_id,
                $event->order_id ?? 'N/A',
                $event->requested_qty,
                $event->available_stock,
                $event->detected_at
            );

            foreach ($admins as $admin) {
                try {
                    Mail::raw($body, function ($mail) use ($admin, $subject) {
                        $mail->to($admin->email)->subject($subject);
                    });
                } catch (\Throwable $e) {
                    Log::channel('erp_stock')->error("HandleStockAnomaly: email to {$admin->email} failed — " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::channel('erp_stock')->error('HandleStockAnomaly listener failed: ' . $e->getMessage());
        }
    }
}
