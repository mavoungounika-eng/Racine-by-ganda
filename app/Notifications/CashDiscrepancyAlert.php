<?php

namespace App\Notifications;

use App\Events\CashDiscrepancyDetected;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * Notification d'alerte discrepancy cash
 */
class CashDiscrepancyAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CashDiscrepancyDetected $event
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // This project uses a custom notifications schema in some environments.
        if (
            Schema::hasTable('notifications')
            && Schema::hasColumns('notifications', ['notifiable_id', 'notifiable_type'])
        ) {
            return ['mail', 'database'];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $session = $this->event->session;
        $difference = $this->event->difference;

        return (new MailMessage)
            ->subject("🚨 Discrepancy Cash Détectée - Session POS #{$session->id}")
            ->greeting("Alerte de sécurité POS")
            ->line("Une discrepancy cash a été détectée lors de la clôture d'une session.")
            ->line("**Session:** #{$session->id}")
            ->line("**Machine:** {$session->machine_id}")
            ->line("**Caissier:** {$session->closer?->name}")
            ->line("**Cash attendu:** {$this->event->expectedCash}€")
            ->line("**Cash compté:** {$this->event->actualCash}€")
            ->line("**Différence:** {$difference}€")
            ->action('Voir la session', route('pos.sessions.z-report', $session))
            ->line('Veuillez vérifier immédiatement.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cash_discrepancy',
            'session_id' => $this->event->session->id,
            'machine_id' => $this->event->session->machine_id,
            'expected_cash' => $this->event->expectedCash,
            'actual_cash' => $this->event->actualCash,
            'difference' => $this->event->difference,
            'closed_by' => $this->event->session->closer?->name,
        ];
    }
}
