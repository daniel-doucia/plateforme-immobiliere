<?php

namespace App\Enums;

enum TypeBien: string
{
    case Chambre = 'chambre';
    case Appartement = 'appartement';
    case Maison = 'maison';
    case Terrain = 'terrain';
    case Hotel = 'hotel';

    public function libelle(): string
    {
        return match ($this) {
            self::Chambre => 'Chambre',
            self::Appartement => 'Appartement',
            self::Maison => 'Maison',
            self::Terrain => 'Terrain',
            self::Hotel => 'Hôtel / nuitée',
        };
    }
}
