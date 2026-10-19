<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutVerification;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\User;

class AnnoncePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Bailleur->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Bailleur->value)
            && BailleurProfil::where('user_id', $user->id)
                ->where('statut_verification', StatutVerification::Verifie)
                ->exists();
    }

    public function update(User $user, Annonce $annonce): bool
    {
        return $this->estProprietaire($user, $annonce)
            && $annonce->statut === StatutAnnonce::Brouillon;
    }

    public function delete(User $user, Annonce $annonce): bool
    {
        return $this->update($user, $annonce);
    }

    public function soumettre(User $user, Annonce $annonce): bool
    {
        return $this->create($user) && $this->update($user, $annonce);
    }

    public function payer(User $user, Annonce $annonce): bool
    {
        return $this->estProprietaire($user, $annonce)
            && $annonce->statut === StatutAnnonce::EnAttentePaiement;
    }

    public function renouveler(User $user, Annonce $annonce): bool
    {
        return $this->estProprietaire($user, $annonce)
            && $annonce->statut === StatutAnnonce::Expiree;
    }

    public function reouvrir(User $user, Annonce $annonce): bool
    {
        return $this->estProprietaire($user, $annonce)
            && in_array($annonce->statut, [StatutAnnonce::Rejetee, StatutAnnonce::EnAttentePaiement], true);
    }

    public function cloturer(User $user, Annonce $annonce): bool
    {
        return $this->estProprietaire($user, $annonce)
            && $annonce->statut->peutPasserA(StatutAnnonce::Cloturee);
    }

    private function estProprietaire(User $user, Annonce $annonce): bool
    {
        return $annonce->user_id === $user->id;
    }
}
