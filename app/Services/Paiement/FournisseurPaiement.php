<?php

namespace App\Services\Paiement;

use App\Models\Commande;

interface FournisseurPaiement
{
    public function code(): string;

    /** Adresse où envoyer le client pour qu'il paie. */
    public function urlPaiement(Commande $commande): string;

    /** Vérifie que le message vient bien du fournisseur. */
    public function signatureValide(string $corps, ?string $signature): bool;

    /** @throws \InvalidArgumentException si le message est mal formé */
    public function analyser(string $corps): EvenementPaiement;
}
