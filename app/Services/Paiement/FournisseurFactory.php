<?php

namespace App\Services\Paiement;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FournisseurFactory
{
    public function pour(string $code): FournisseurPaiement
    {
        // Le faux fournisseur n'existe pas en production
        if ($code === 'faux' && ! app()->isProduction()) {
            return new FournisseurFaux();
        }

        throw new NotFoundHttpException('Fournisseur de paiement inconnu.');
    }

    public function parDefaut(): FournisseurPaiement
    {
        return $this->pour((string) config('paiement.fournisseur_par_defaut'));
    }
}
