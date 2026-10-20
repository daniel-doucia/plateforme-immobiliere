<?php

namespace App\Console\Commands;

use App\Models\AlerteRecherche;
use App\Models\Annonce;
use App\Notifications\NouvellesAnnoncesAlerte;
use Illuminate\Console\Command;
use Throwable;

class EnvoyerAlertes extends Command
{
    protected $signature = 'alertes:envoyer';

    protected $description = 'Envoie un e-mail pour chaque alerte dont de nouvelles annonces correspondent';

    public function handle(): int
    {
        // Instant fixé une fois : rien ne se perd entre deux passages
        $maintenant = now();
        $envoyes = 0;

        AlerteRecherche::with(['user', 'ville', 'quartier.ville'])
            ->where('actif', true)
            ->chunkById(100, function ($alertes) use ($maintenant, &$envoyes) {
                foreach ($alertes as $alerte) {
                    try {
                        $depuis = $alerte->derniere_verification_le ?? $alerte->created_at;

                        $base = Annonce::publiees()
                            ->filtrer($alerte->parametres())
                            ->where('user_id', '!=', $alerte->user_id)
                            ->where('publiee_le', '>', $depuis)
                            ->where('publiee_le', '<=', $maintenant);

                        $total = (clone $base)->count();

                        if ($total > 0) {
                            $annonces = $base->with('quartier.ville')->orderByDesc('publiee_le')->limit(5)->get();
                            $alerte->user->notify(new NouvellesAnnoncesAlerte($alerte, $annonces, $total));
                            $envoyes++;
                        }

                        $alerte->forceFill(['derniere_verification_le' => $maintenant])->save();
                    } catch (Throwable $e) {
                        // Un échec ne bloque pas les autres alertes ; celle-ci sera retentée demain
                        report($e);
                    }
                }
            });

        $this->info("{$envoyes} e-mail(s) envoyé(s).");

        return self::SUCCESS;
    }
}
