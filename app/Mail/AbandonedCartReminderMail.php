<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cart $cart,
        public User $user,
        public int  $reminderNumber  // 1, 2 ou 3
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            1 => 'Vous avez oublié quelque chose… 🛍️',
            2 => 'Votre panier vous attend chez RACINE BY GANDA',
            3 => 'Dernière chance — votre panier expire bientôt',
        ];

        return new Envelope(
            subject: $subjects[$this->reminderNumber] ?? 'Votre panier RACINE BY GANDA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned-cart',
            with: [
                'user'           => $this->user,
                'cart'           => $this->cart,
                'items'          => $this->cart->items()->with('product')->get(),
                'reminderNumber' => $this->reminderNumber,
                'cartUrl'        => route('cart.index'),
                'shopUrl'        => route('frontend.shop'),
            ],
        );
    }
}
