<?php

namespace App\Enums;

enum Periodicite: string
{
    case Mensuel = 'mensuel';
    case Annuel = 'annuel';

    public function libelle(): string
    {
        return match ($this) {
            self::Mensuel => 'par mois',
            self::Annuel => 'par an',
        };
    }
}
