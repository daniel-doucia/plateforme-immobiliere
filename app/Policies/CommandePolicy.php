<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\User;

class CommandePolicy
{
    public function view(User $user, Commande $commande): bool
    {
        return $commande->user_id === $user->id;
    }
}
