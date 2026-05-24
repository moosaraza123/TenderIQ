<?php

namespace App\Modules\Alert\Mail;

use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class TenderAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $unsubscribeUrl;

    public function __construct(
        public readonly User       $user,
        public readonly Collection $tenders,
    ) {
        $this->unsubscribeUrl = URL::signedRoute('alerts.unsubscribe', ['user' => $user->id]);
    }

    public function envelope(): Envelope
    {
        $count = $this->tenders->count();
        return new Envelope(
            subject: "{$count} new " . \Illuminate\Support\Str::plural('tender', $count) . " match your alert | TenderIQ",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tender-alert',
        );
    }
}
