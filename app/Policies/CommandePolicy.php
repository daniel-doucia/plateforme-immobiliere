<?php

namespace App\Policies;

use App\Enums\StatutCommande;
use App\Models\Commande;
use App\Models\User;

class CommandePolicy
{
    public function view(User $user, Commande $commande): bool
    {
        return $commande->user_id === $user->id;
    }

    public function payer(User $user, Commande $commande): bool
    {
        return $commande->user_id === $user->id
            && $commande->statut === StatutCommande::EnAttente;
    }
}
