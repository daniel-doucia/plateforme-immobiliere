<?php

namespace App\Services\Paiement;

use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TraiterPaiement
{
    public function appliquer(FournisseurPaiement $fournisseur, EvenementPaiement $evenement): string
    {
        return DB::transaction(function () use ($fournisseur, $evenement) {
            // Idempotence : un événement déjà reçu n'est jamais traité deux fois
            $nouveau = DB::table('webhook_events')->insertOrIgnore([
                'fournisseur' => $fournisseur->code(),
                'event_id' => $evenement->eventId,
                'payload' => json_encode($evenement->brut),
                'traite_le' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($nouveau === 0) {
                return 'deja_traite';
            }

            $commande = Commande::where('reference', $evenement->reference)->lockForUpdate()->first();
            abort_if($commande === null, 404, 'Commande introuvable.');

            if ($commande->statut !== StatutCommande::EnAttente) {
                return 'ignore';
            }

            if ($evenement->montant !== $commande->montant) {
                Log::warning('Paiement refusé : montant inattendu', [
                    'reference' => $commande->reference,
                    'attendu' => $commande->montant,
                    'recu' => $evenement->montant,
                ]);
                abort(422, 'Montant inattendu.');
            }

            if (! $evenement->reussi) {
                $commande->forceFill(['statut' => StatutCommande::Echouee])->save();
                $this->enregistrer($commande, $fournisseur, $evenement, 'echoue');

                return 'echec_enregistre';
            }

            $this->enregistrer($commande, $fournisseur, $evenement, 'reussi');
            $commande->forceFill(['statut' => StatutCommande::Payee, 'payee_le' => now()])->save();

            $annonce = Annonce::withTrashed()->lockForUpdate()->find($commande->annonce_id);
            if ($annonce !== null && ! $annonce->trashed()) {
                $this->envoyerEnModeration($annonce);
            }

            return 'paye';
        });
    }

    private function enregistrer(Commande $commande, FournisseurPaiement $fournisseur, EvenementPaiement $evenement, string $statut): void
    {
        Paiement::create([
            'commande_id' => $commande->id,
            'fournisseur' => $fournisseur->code(),
            'reference_fournisseur' => $evenement->eventId,
            'montant' => $evenement->montant,
            'statut' => $statut,
            'payload' => $evenement->brut,
            'paye_le' => $statut === 'reussi' ? now() : null,
        ]);
    }

    private function envoyerEnModeration(Annonce $annonce): void
    {
        // Le bailleur a pu rouvrir l'annonce entre la commande et le paiement : on la remet dans le circuit
        if ($annonce->statut === StatutAnnonce::Rejetee) {
            $annonce->transitionner(StatutAnnonce::Brouillon);
        }

        if (in_array($annonce->statut, [StatutAnnonce::Brouillon, StatutAnnonce::Expiree], true)) {
            $annonce->transitionner(StatutAnnonce::EnAttentePaiement);
        }

        if ($annonce->statut === StatutAnnonce::EnAttentePaiement) {
            $annonce->transitionner(StatutAnnonce::EnModeration);

            return;
        }

        Log::warning('Paiement reçu pour une annonce dans un état inattendu', [
            'annonce_id' => $annonce->id,
            'statut' => $annonce->statut->value,
        ]);
    }
}
