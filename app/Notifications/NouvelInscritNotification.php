<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NouvelInscritNotification extends Notification
{
    public function __construct(
        public string $type,   // 'agent' | 'utilisateur' | 'visite'
        public string $nom,
        public string $email,
    ) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $labels = [
            'agent'       => '🏢 Nouvel agent',
            'utilisateur' => '👤 Nouvel utilisateur',
            'visite'      => '🏠 Nouvelle demande de visite',
        ];

        return (new MailMessage)
            ->subject('[SIMMo] ' . ($labels[$this->type] ?? 'Nouvelle inscription'))
            ->greeting('Bonjour Admin,')
            ->line(($labels[$this->type] ?? 'Un nouvel inscrit') . ' vient de s\'enregistrer sur SIMMo.')
            ->line('**Nom :** ' . $this->nom)
            ->line('**Email :** ' . $this->email)
            ->line('**Type :** ' . ucfirst($this->type))
            ->line('**Date :** ' . now()->format('d/m/Y à H:i'))
            ->action('Voir le dashboard', url('/admin'))
            ->salutation('L\'équipe SIMMo');
    }
}