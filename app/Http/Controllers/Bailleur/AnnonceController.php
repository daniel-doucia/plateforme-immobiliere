<?php

namespace App\Http\Controllers\Bailleur;

use App\Enums\Periodicite;
use App\Enums\StatutAnnonce;
use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bailleur\AnnonceRequest;
use App\Models\Annonce;
use App\Models\Ville;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AnnonceController extends Controller
{
    public function index(Request $request): Response
    {
        $annonces = Annonce::with('quartier.ville')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Annonce $a) => $this->resume($a));

        return Inertia::render('Bailleur/Annonces/Index', [
            'annonces' => $annonces,
            'peut_creer' => $request->user()->can('create', Annonce::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Annonce::class);

        return $this->formulaire(null);
    }

    public function store(AnnonceRequest $request): RedirectResponse
    {
        $annonce = new Annonce($this->donneesValidees($request));
        $annonce->user_id = $request->user()->id; // jamais issu du formulaire
        $annonce->save();

        return redirect()->route('bailleur.annonces.photos.index', $annonce);
    }

    public function edit(Annonce $annonce): Response
    {
        Gate::authorize('update', $annonce);

        return $this->formulaire($annonce);
    }

    public function update(AnnonceRequest $request, Annonce $annonce): RedirectResponse
    {
        $annonce->update($this->donneesValidees($request));

        return redirect()->route('bailleur.annonces.photos.index', $annonce);
    }

    public function soumettre(Annonce $annonce): RedirectResponse
    {
        Gate::authorize('soumettre', $annonce);
        app(\App\Services\Annonces\SoumettreAnnonce::class)->executer($annonce);

        return back();
    }

    public function rouvrir(Annonce $annonce): RedirectResponse
    {
        Gate::authorize('reouvrir', $annonce);
        $annonce->transitionner(StatutAnnonce::Brouillon);

        return back();
    }

    public function cloturer(Annonce $annonce): RedirectResponse
    {
        Gate::authorize('cloturer', $annonce);
        $annonce->transitionner(StatutAnnonce::Cloturee);

        return back();
    }

    public function destroy(Annonce $annonce): RedirectResponse
    {
        Gate::authorize('delete', $annonce);
        $annonce->delete();

        return back();
    }

    private function donneesValidees(AnnonceRequest $request): array
    {
        $data = $request->validated();

        if ($data['type_offre'] === TypeOffre::Vente->value) {
            $data['periodicite'] = null;
            $data['caution'] = null;
        }

        if ($data['type_bien'] === TypeBien::Terrain->value) {
            $data['nb_pieces'] = $data['nb_chambres'] = $data['nb_salles_bain'] = null;
        }

        return $data;
    }

    private function resume(Annonce $a): array
    {
        return [
            'id' => $a->id,
            'titre' => $a->titre,
            'statut' => $a->statut->value,
            'statut_libelle' => $a->statut->libelle(),
            'type_bien' => $a->type_bien->libelle(),
            'type_offre' => $a->type_offre->libelle(),
            'prix' => $a->prix,
            'periodicite' => $a->periodicite?->libelle(),
            'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
            'motif_rejet' => $a->motif_rejet,
            'expire_le' => $a->expire_le?->toIso8601String(),
            'peut' => [
                'modifier' => Gate::allows('update', $a),
                'soumettre' => Gate::allows('soumettre', $a),
                'rouvrir' => Gate::allows('reouvrir', $a),
                'cloturer' => Gate::allows('cloturer', $a),
                'renouveler' => Gate::allows('renouveler', $a),
                'supprimer' => Gate::allows('delete', $a),
            ],
        ];
    }

    private function formulaire(?Annonce $annonce): Response
    {
        $villes = Ville::with(['quartiers' => fn ($q) => $q->orderBy('nom')])
            ->orderBy('nom')
            ->get()
            ->map(fn (Ville $v) => [
                'id' => $v->id,
                'nom' => $v->nom,
                'quartiers' => $v->quartiers->map(fn ($q) => ['id' => $q->id, 'nom' => $q->nom])->values(),
            ])
            ->filter(fn (array $v) => count($v['quartiers']) > 0)
            ->values();

        $options = fn (array $cas) => array_map(
            fn ($c) => ['value' => $c->value, 'label' => $c->libelle()],
            $cas
        );

        return Inertia::render('Bailleur/Annonces/Form', [
            'annonce' => $annonce ? [
                'id' => $annonce->id,
                'quartier_id' => $annonce->quartier_id,
                'titre' => $annonce->titre,
                'description' => $annonce->description,
                'type_bien' => $annonce->type_bien->value,
                'type_offre' => $annonce->type_offre->value,
                'prix' => $annonce->prix,
                'periodicite' => $annonce->periodicite?->value,
                'caution' => $annonce->caution,
                'superficie' => $annonce->superficie,
                'nb_pieces' => $annonce->nb_pieces,
                'nb_chambres' => $annonce->nb_chambres,
                'nb_salles_bain' => $annonce->nb_salles_bain,
                'adresse_indicative' => $annonce->adresse_indicative,
            ] : null,
            'villes' => $villes,
            'types_bien' => $options(TypeBien::cases()),
            'types_offre' => $options(TypeOffre::cases()),
            'periodicites' => $options(Periodicite::cases()),
        ]);
    }
}
