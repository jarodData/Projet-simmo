<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UtilisateurVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $utilisateur;
    public $token;

    public function __construct($utilisateur, $token)
    {
        $this->utilisateur = $utilisateur;
        $this->token       = $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifiez votre compte SIMMo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.utilisateur_verification',
        );
    }
}