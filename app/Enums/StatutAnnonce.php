<?php

namespace App\Enums;

enum StatutAnnonce: string
{
    case Brouillon = 'brouillon';
    case EnAttentePaiement = 'en_attente_paiement';
    case EnModeration = 'en_moderation';
    case Publiee = 'publiee';
    case Rejetee = 'rejetee';
    case Expiree = 'expiree';
    case Cloturee = 'cloturee';

    /** @return array<int, self> */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::Brouillon => [self::EnAttentePaiement, self::Cloturee],
            self::EnAttentePaiement => [self::EnModeration, self::Brouillon, self::Cloturee],
            self::EnModeration => [self::Publiee, self::Rejetee],
            self::Rejetee => [self::Brouillon, self::Cloturee],
            self::Publiee => [self::Expiree, self::Cloturee],
            self::Expiree => [self::EnAttentePaiement, self::Cloturee],
            self::Cloturee => [],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return in_array($cible, $this->transitionsPossibles(), true);
    }

    public function libelle(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::EnAttentePaiement => 'En attente de paiement',
            self::EnModeration => 'En modération',
            self::Publiee => 'Publiée',
            self::Rejetee => 'Rejetée',
            self::Expiree => 'Expirée',
            self::Cloturee => 'Clôturée',
        };
    }
}
