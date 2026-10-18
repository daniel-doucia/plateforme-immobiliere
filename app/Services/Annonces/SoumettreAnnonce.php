<?php

namespace App\Services\Annonces;

use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Models\Annonce;
use App\Models\Commande;
use Illuminate\Support\Facades\DB;

class SoumettreAnnonce
{
    public function executer(Annonce $annonce): void
    {
        DB::transaction(function () use ($annonce) {
            $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

            // Correction gratuite : un paiement déjà confirmé et non utilisé évite de repayer
            $paiementDisponible = Commande::where('annonce_id', $annonce->id)
                ->where('statut', StatutCommande::Payee->value)
                ->whereNull('consommee_le')
                ->exists();

            if ($paiementDisponible) {
                $annonce->transitionner(StatutAnnonce::EnModeration);
            }
        });
    }
}
