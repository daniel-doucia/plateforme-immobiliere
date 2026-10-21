<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Annonce;
use App\Services\Statistiques\StatistiquesAnnonces;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StatistiquesAnnonces $statistiques): Response
    {
        $utilisateur = $request->user();
        $estBailleur = $utilisateur->hasRole(Role::Bailleur->value);

        // Chacun ne voit que ses propres chiffres et ses propres annonces
        $recentes = $estBailleur
            ? Annonce::with(['quartier.ville', 'photos'])
                ->where('user_id', $utilisateur->id)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Annonce $a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'statut' => $a->statut->value,
                    'statut_libelle' => $a->statut->libelle(),
                    'prix' => $a->prix,
                    'periodicite' => $a->periodicite?->libelle(),
                    'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
                    'photo' => $a->photos->first()?->url(),
                ])
                ->values()
            : collect();

        return Inertia::render('Dashboard', [
            'stats' => $estBailleur
                ? $statistiques->parBailleur([$utilisateur->id])[$utilisateur->id]
                : null,
            'annonces_recentes' => $recentes,
        ]);
    }
}
