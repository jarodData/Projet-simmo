<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageAgent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from    : new \Illuminate\Mail\Mailables\Address($this->data['email'], $this->data['prenom'] . ' ' . $this->data['nom']),
            replyTo : [$this->data['email']],
            subject : '[SIMMo] ' . $this->data['sujet'],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlTemplate: 'emails.message-agent',
            with        : ['data' => $this->data],
        );
    }
}