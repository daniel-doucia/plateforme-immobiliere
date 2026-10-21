<?php

namespace App\Http\Controllers\Bailleur;

use App\Enums\StatutAnnonce;
use App\Enums\StatutVerification;
use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use App\Models\BailleurProfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RecapitulatifController extends Controller
{
    public function __invoke(Request $request, Annonce $annonce): Response
    {
        abort_unless($annonce->user_id === $request->user()->id, 403);

        $annonce->load(['quartier.ville', 'photos']);

        $verifie = BailleurProfil::where('user_id', $request->user()->id)
            ->where('statut_verification', StatutVerification::Verifie)
            ->exists();

        return Inertia::render('Bailleur/Annonces/Recapitulatif', [
            'annonce' => [
                'id' => $annonce->id,
                'titre' => $annonce->titre,
                'description' => $annonce->description,
                'type_bien' => $annonce->type_bien->libelle(),
                'type_offre' => $annonce->type_offre->libelle(),
                'prix' => $annonce->prix,
                'periodicite' => $annonce->periodicite?->libelle(),
                'caution' => $annonce->caution,
                'superficie' => $annonce->superficie,
                'nb_pieces' => $annonce->nb_pieces,
                'nb_chambres' => $annonce->nb_chambres,
                'nb_salles_bain' => $annonce->nb_salles_bain,
                'adresse_indicative' => $annonce->adresse_indicative,
                'lieu' => $annonce->quartier->nom.', '.$annonce->quartier->ville->nom,
                'statut' => $annonce->statut->value,
                'statut_libelle' => $annonce->statut->libelle(),
            ],
            'photos' => $annonce->photos
                ->map(fn (AnnoncePhoto $p) => ['id' => $p->id, 'url' => $p->url()])
                ->values(),
            'controles' => [
                'compte_verifie' => $verifie,
                'photo' => ! config('annonces.photo_obligatoire') || $annonce->photos->isNotEmpty(),
                'brouillon' => $annonce->statut === StatutAnnonce::Brouillon,
            ],
            'peut' => [
                'modifier' => Gate::allows('update', $annonce),
                'soumettre' => Gate::allows('soumettre', $annonce),
            ],
        ]);
    }
}
