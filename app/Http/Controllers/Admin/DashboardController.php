<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutVerification;
use App\Http\Controllers\Controller;
use App\Models\BailleurProfil;
use App\Models\User;
use App\Services\Statistiques\StatistiquesAnnonces;
use App\Services\Statistiques\StatistiquesPlateforme;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(StatistiquesAnnonces $statistiques, StatistiquesPlateforme $plateforme): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'utilisateurs' => User::count(),
                'bailleurs_en_attente' => BailleurProfil::where('statut_verification', StatutVerification::EnAttente)->count(),
            ],
            'annonces' => $statistiques->globales(),
            'repartition' => $plateforme->repartition(),
            'evolution' => $plateforme->evolution(6),
            'comptes' => $plateforme->comptes(),
            'revenus' => $plateforme->revenus(),
            'en_attente' => $plateforme->enAttente(),
            'derniers_paiements' => $plateforme->derniersPaiements(),
        ]);
    }
}
