<?php

namespace App\Modules\Payment\Mail;

use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User  $user,
        public readonly array $payload
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your TenderIQ subscription is active');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.confirmed',
            with: [
                'user'   => $this->user,
                'amount' => ($this->payload['data']['object']['amount_paid'] ?? 0) / 100,
            ]
        );
    }
}
