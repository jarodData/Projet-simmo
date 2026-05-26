<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $agent;
    public $token;

    public function __construct($agent, $token)
    {
        $this->agent = $agent;
        $this->token = $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifiez votre compte Agent SIMMo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent_verification',
        );
    }
}