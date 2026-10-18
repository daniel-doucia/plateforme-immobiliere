<?php

namespace App\Http\Controllers\Paiement;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\Paiement\FournisseurFactory;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PaiementController extends Controller
{
    public function payer(Commande $commande, FournisseurFactory $fabrique)
    {
        Gate::authorize('payer', $commande);

        return Inertia::location($fabrique->parDefaut()->urlPaiement($commande));
    }
}
