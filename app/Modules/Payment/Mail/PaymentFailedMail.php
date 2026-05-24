<?php

namespace App\Modules\Payment\Mail;

use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment failed — action required');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.failed',
            with: ['user' => $this->user]
        );
    }
}
