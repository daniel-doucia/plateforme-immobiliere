<?php

namespace App\Services\Moderation;

use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Moderation;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ModererAnnonce
{
    public function valider(Annonce $annonce, User $moderateur): void
    {
        DB::transaction(function () use ($annonce, $moderateur) {
            $annonce = $this->verrouiller($annonce);

            // Un paiement confirmé et pas encore utilisé est indispensable pour publier
            $commande = Commande::where('annonce_id', $annonce->id)
                ->where('statut', StatutCommande::Payee->value)
                ->whereNull('consommee_le')
                ->orderBy('payee_le')
                ->lockForUpdate()
                ->first();

            if ($commande === null) {
                throw new DomainException('Impossible de publier : aucun paiement disponible pour cette annonce.');
            }

            $annonce->transitionner(StatutAnnonce::Publiee);

            // La durée démarre à la validation, pas au paiement ;
            // le rappel est remis à zéro pour que ce nouveau cycle soit rappelé à son tour
            $annonce->forceFill([
                'expire_le' => now()->addDays($commande->duree_jours),
                'rappel_expiration_le' => null,
                'motif_rejet' => null,
            ])->save();

            $commande->forceFill(['consommee_le' => now()])->save();

            Moderation::create([
                'annonce_id' => $annonce->id,
                'moderateur_id' => $moderateur->id,
                'decision' => 'valide',
            ]);
        });
    }

    public function rejeter(Annonce $annonce, User $moderateur, string $motif): void
    {
        DB::transaction(function () use ($annonce, $moderateur, $motif) {
            $annonce = $this->verrouiller($annonce);

            $annonce->transitionner(StatutAnnonce::Rejetee);
            $annonce->forceFill(['motif_rejet' => $motif])->save();

            Moderation::create([
                'annonce_id' => $annonce->id,
                'moderateur_id' => $moderateur->id,
                'decision' => 'rejete',
                'motif' => $motif,
            ]);
        });
    }

    private function verrouiller(Annonce $annonce): Annonce
    {
        $courante = Annonce::lockForUpdate()->findOrFail($annonce->id);

        if ($courante->statut !== StatutAnnonce::EnModeration) {
            throw new DomainException('Cette annonce n\'est plus en attente de modération.');
        }

        return $courante;
    }
}
