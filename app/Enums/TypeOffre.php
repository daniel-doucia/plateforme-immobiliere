<?php

namespace App\Enums;

enum TypeOffre: string
{
    case Location = 'location';
    case Vente = 'vente';

    public function libelle(): string
    {
        return match ($this) {
            self::Location => 'À louer',
            self::Vente => 'À vendre',
        };
    }
}
