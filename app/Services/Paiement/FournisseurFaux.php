<?php

namespace App\Services\Paiement;

use App\Models\Commande;
use InvalidArgumentException;

/** Fournisseur de TEST : aucun argent réel. Jamais actif en production. */
class FournisseurFaux implements FournisseurPaiement
{
    public function code(): string
    {
        return 'faux';
    }

    public function urlPaiement(Commande $commande): string
    {
        return route('paiement.faux.show', $commande->reference);
    }

    public function signer(string $corps): string
    {
        $secret = (string) config('paiement.faux.secret');

        return $secret === '' ? '' : hash_hmac('sha256', $corps, $secret);
    }

    public function signatureValide(string $corps, ?string $signature): bool
    {
        $attendue = $this->signer($corps);

        if ($attendue === '' || blank($signature)) {
            return false;
        }

        return hash_equals($attendue, $signature);
    }

    public function analyser(string $corps): EvenementPaiement
    {
        $donnees = json_decode($corps, true);

        if (! is_array($donnees) || ! isset($donnees['event_id'], $donnees['reference'], $donnees['montant'], $donnees['statut'])) {
            throw new InvalidArgumentException('Événement de paiement invalide.');
        }

        return new EvenementPaiement(
            eventId: (string) $donnees['event_id'],
            reference: (string) $donnees['reference'],
            montant: (int) $donnees['montant'],
            reussi: $donnees['statut'] === 'reussi',
            brut: $donnees,
        );
    }
}
