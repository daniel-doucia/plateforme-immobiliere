<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\Statistiques\StatistiquesAnnonces;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StatistiquesAnnonces $statistiques): Response
    {
        $utilisateur = $request->user();

        return Inertia::render('Dashboard', [
            // Chacun ne voit que ses propres chiffres
            'stats' => $utilisateur->hasRole(Role::Bailleur->value)
                ? $statistiques->parBailleur([$utilisateur->id])[$utilisateur->id]
                : null,
        ]);
    }
}
