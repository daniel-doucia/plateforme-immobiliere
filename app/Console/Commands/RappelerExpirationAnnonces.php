<?php

namespace App\Console\Commands;

use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use App\Notifications\AnnonceBientotExpiree;
use Illuminate\Console\Command;
use Throwable;

class RappelerExpirationAnnonces extends Command
{
    protected $signature = 'annonces:rappeler-expiration {--jours=3 : Nombre de jours avant l\'échéance}';

    protected $description = 'Prévient les bailleurs dont une annonce expire bientôt (un seul rappel par publication)';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('jours'));
        $envoyes = 0;

        Annonce::with('user')
            ->where('statut', StatutAnnonce::Publiee)
            ->whereNull('rappel_expiration_le')
            ->where('expire_le', '>', now())
            ->where('expire_le', '<=', now()->addDays($jours))
            ->chunkById(100, function ($annonces) use (&$envoyes) {
                foreach ($annonces as $annonce) {
                    try {
                        $annonce->user->notify(new AnnonceBientotExpiree($annonce));
                        $annonce->forceFill(['rappel_expiration_le' => now()])->save();
                        $envoyes++;
                    } catch (Throwable $e) {
                        // Un échec d'envoi ne bloque pas les autres rappels ; il sera retenté demain
                        report($e);
                    }
                }
            });

        $this->info("{$envoyes} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
