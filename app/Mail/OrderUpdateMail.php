<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $platform,
        public readonly string $mailSubject,
        public readonly string $bodyText,
        public readonly string $actionUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->platform.' - '.$this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.operational-alert',
            with: [
                'platform' => $this->platform,
                'subject' => $this->mailSubject,
                'body' => $this->bodyText,
                'url' => $this->actionUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
