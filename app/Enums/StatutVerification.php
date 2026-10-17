<?php

namespace App\Enums;

enum StatutVerification: string
{
    case EnAttente = 'en_attente';
    case Verifie = 'verifie';
    case Rejete = 'rejete';
}
