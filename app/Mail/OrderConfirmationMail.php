<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly User  $user,
        public readonly string $recipient // 'client' | 'admin'
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->recipient === 'admin'
            ? "Nouvelle commande #{$this->order->id} – NutriSport"
            : "Confirmation de votre commande #{$this->order->id}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order'     => $this->order,
                'user'      => $this->user,
                'recipient' => $this->recipient,
            ]
        );
    }
}
