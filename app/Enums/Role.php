<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Moderateur = 'moderateur';
    case Bailleur = 'bailleur';
    case Client = 'client';
}
