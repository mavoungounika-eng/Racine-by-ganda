<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRelaunchedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Commande #' . $this->order->id . ' relancée')
            ->line('La commande #' . $this->order->id . ' a été restaurée et relancée par le client.')
            ->line('Montant : ' . number_format($this->order->total_amount, 2) . ' XAF')
            ->action('Voir la commande', url('/admin/orders/' . $this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'order_relaunched',
            'order_id' => $this->order->id,
            'message'  => 'Commande #' . $this->order->id . ' relancée par le client.',
            'amount'   => $this->order->total_amount,
        ];
    }
}
