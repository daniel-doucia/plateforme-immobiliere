<?php

namespace App\Notifications;

use App\Models\Annonce;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnonceBientotExpiree extends Notification
{
    public function __construct(public Annonce $annonce) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre annonce expire bientôt')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre annonce « '.$this->annonce->titre.' » expire le '.$this->annonce->expire_le->format('d/m/Y').'.')
            ->line('Une fois expirée, elle ne sera plus visible. Vous pourrez la renouveler depuis votre espace.')
            ->action('Gérer mes annonces', route('bailleur.annonces.index'));
    }
}
