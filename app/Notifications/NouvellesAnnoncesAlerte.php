<?php

namespace App\Notifications;

use App\Models\AlerteRecherche;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NouvellesAnnoncesAlerte extends Notification
{
    public function __construct(
        public AlerteRecherche $alerte,
        public Collection $annonces,
        public int $total,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->total > 1
                ? "{$this->total} nouvelles annonces pour votre alerte"
                : '1 nouvelle annonce pour votre alerte')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre alerte « '.$this->alerte->resume().' » a trouvé du nouveau :');

        foreach ($this->annonces as $annonce) {
            $mail->line(
                '• '.$annonce->titre.' · '.$annonce->quartier->nom.' · '
                .number_format($annonce->prix, 0, ',', ' ').' FCFA : '
                .route('annonces.show', $annonce->slug)
            );
        }

        $reste = $this->total - $this->annonces->count();
        if ($reste > 0) {
            $mail->line("… et {$reste} autre(s) annonce(s).");
        }

        return $mail
            ->action('Voir les annonces', route('annonces.index', $this->alerte->parametres()))
            ->line('Vous pouvez mettre cette alerte en pause ou la supprimer depuis « Mes alertes ».');
    }
}
