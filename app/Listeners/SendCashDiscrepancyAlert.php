<?php

namespace App\Listeners;

use App\Events\CashDiscrepancyDetected;
use App\Models\User;
use App\Notifications\CashDiscrepancyAlert;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour les discrepancies cash
 * Envoie une alerte aux administrateurs
 */
class SendCashDiscrepancyAlert
{
    /**
     * Handle the event.
     */
    public function handle(CashDiscrepancyDetected $event): void
    {
        $session = $event->session;
        $difference = $event->difference;

        Log::warning('Cash discrepancy detected', [
            'session_id' => $session->id,
            'machine_id' => $session->machine_id,
            'expected_cash' => $event->expectedCash,
            'actual_cash' => $event->actualCash,
            'difference' => $difference,
            'closed_by' => $session->closer?->name,
        ]);

        // Envoyer notification aux admins (compat avec modèle User basé sur role_id/slug).
        $admins = User::query()->admins()->get();

        foreach ($admins as $admin) {
            $admin->notify(new CashDiscrepancyAlert($event));
        }
    }
}
