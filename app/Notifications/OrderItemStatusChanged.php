<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderItemStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected OrderItem $item,
        protected Order $order,
        protected ?string $customMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->customMessage ?? $this->getMessageForStatus();
        $subject = $this->getSubjectForStatus();

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.order-item-status-changed', [
                'notifiable'  => $notifiable,
                'item'        => $this->item,
                'order'       => $this->order,
                'bodyMessage' => $message,
                'subject'     => $subject,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'order_item_status_changed',
            'title'      => $this->getSubjectForStatus(),
            'message'    => $this->customMessage ?? $this->getMessageForStatus(),
            'order_id'   => $this->order->id,
            'item_id'    => $this->item->id,
            'action_url' => route('profile.orders.show', $this->order->id),
        ];
    }

    private function getMessageForStatus(): string
    {
        $name = $this->item->product?->title ?? 'votre article';

        return match ($this->item->status) {
            'shipped'          => "Votre article {$name} a été expédié. Vous le recevrez sous 2-5 jours ouvrés.",
            'delivered'        => "Votre article {$name} a été marqué comme livré. Vous pouvez confirmer la réception depuis votre espace client.",
            'refunded'         => "Votre remboursement pour {$name} a été traité. Il apparaîtra sous 3-5 jours ouvrés.",
            'return_requested' => "Votre demande de retour pour {$name} a bien été reçue. Nous reviendrons vers vous sous 48h.",
            'disputed'         => "Votre signalement concernant {$name} a été pris en compte par notre équipe.",
            default             => "Le statut de votre article {$name} a été mis à jour.",
        };
    }

    private function getSubjectForStatus(): string
    {
        return match ($this->item->status) {
            'shipped'          => 'Votre article a été expédié',
            'delivered'        => 'Votre article a été livré',
            'refunded'         => 'Votre remboursement a été traité',
            'return_requested' => 'Demande de retour reçue',
            'disputed'         => 'Signalement pris en compte',
            default             => 'Mise à jour de votre commande',
        };
    }
}
