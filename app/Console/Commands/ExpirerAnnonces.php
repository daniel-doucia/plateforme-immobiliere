<?php

namespace App\Console\Commands;

use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use DomainException;
use Illuminate\Console\Command;

class ExpirerAnnonces extends Command
{
    protected $signature = 'annonces:expirer';

    protected $description = 'Passe en « Expirée » les annonces publiées dont la durée est dépassée';

    public function handle(): int
    {
        $total = 0;

        Annonce::where('statut', StatutAnnonce::Publiee)
            ->whereNotNull('expire_le')
            ->where('expire_le', '<=', now())
            ->chunkById(100, function ($annonces) use (&$total) {
                foreach ($annonces as $annonce) {
                    try {
                        $annonce->transitionner(StatutAnnonce::Expiree);
                        $total++;
                    } catch (DomainException $e) {
                        report($e);
                    }
                }
            });

        $this->info("{$total} annonce(s) expirée(s).");

        return self::SUCCESS;
    }
}
