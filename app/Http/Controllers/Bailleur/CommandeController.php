<?php

namespace App\Http\Controllers\Bailleur;

use App\Enums\StatutCommande;
use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Forfait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CommandeController extends Controller
{
    public function create(Annonce $annonce): Response
    {
        Gate::authorize('payer', $annonce);

        $forfaits = Forfait::actifs()
            ->pour($annonce->type_bien)
            ->orderBy('ordre')
            ->get()
            ->map(fn (Forfait $f) => [
                'id' => $f->id,
                'nom' => $f->nom,
                'duree_jours' => $f->duree_jours,
                'prix' => $f->prix,
            ]);

        return Inertia::render('Bailleur/Commandes/Create', [
            'annonce' => ['id' => $annonce->id, 'titre' => $annonce->titre],
            'forfaits' => $forfaits,
        ]);
    }

    public function store(Request $request, Annonce $annonce): RedirectResponse
    {
        Gate::authorize('payer', $annonce);

        $data = $request->validate(['forfait_id' => ['required', 'integer']]);

        // Le forfait doit exister, être actif et convenir au type de bien (sinon 404)
        $forfait = Forfait::actifs()->pour($annonce->type_bien)->findOrFail($data['forfait_id']);

        $commande = DB::transaction(function () use ($annonce, $forfait, $request) {
            // Une seule commande en attente par annonce : les précédentes sont annulées
            Commande::where('annonce_id', $annonce->id)
                ->where('statut', StatutCommande::EnAttente->value)
                ->update(['statut' => StatutCommande::Annulee->value]);

            // Le montant vient du forfait en base, jamais du navigateur
            return Commande::create([
                'user_id' => $request->user()->id,
                'annonce_id' => $annonce->id,
                'forfait_id' => $forfait->id,
                'libelle' => $forfait->nom,
                'duree_jours' => $forfait->duree_jours,
                'montant' => $forfait->prix,
            ]);
        });

        return redirect()->route('bailleur.commandes.show', $commande);
    }

    public function show(Commande $commande): Response
    {
        Gate::authorize('view', $commande);

        $commande->load('annonce:id,titre');

        return Inertia::render('Bailleur/Commandes/Show', [
            'commande' => [
                'reference' => $commande->reference,
                'libelle' => $commande->libelle,
                'duree_jours' => $commande->duree_jours,
                'montant' => $commande->montant,
                'statut' => $commande->statut->value,
                'statut_libelle' => $commande->statut->libelle(),
                'annonce_titre' => $commande->annonce->titre,
            ],
        ]);
    }
}
