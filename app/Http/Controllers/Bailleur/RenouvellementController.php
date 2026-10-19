<?php

namespace App\Http\Controllers\Bailleur;

use App\Enums\StatutAnnonce;
use App\Http\Controllers\Controller;
use App\Models\Annonce;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RenouvellementController extends Controller
{
    public function __invoke(Annonce $annonce): RedirectResponse
    {
        Gate::authorize('renouveler', $annonce);

        $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

        return redirect()->route('bailleur.commandes.create', $annonce);
    }
}
