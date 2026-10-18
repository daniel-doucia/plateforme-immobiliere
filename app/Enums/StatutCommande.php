<?php

namespace App\Enums;

enum StatutCommande: string
{
    case EnAttente = 'en_attente';
    case Payee = 'payee';
    case Echouee = 'echouee';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de paiement',
            self::Payee => 'Payée',
            self::Echouee => 'Échouée',
            self::Annulee => 'Annulée',
        };
    }
}
