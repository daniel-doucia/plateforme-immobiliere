<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\Commande;
use App\Services\Moderation\ModererAnnonce;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(): Response
    {
        $page = Annonce::with(['user:id,name,email,telephone', 'quartier.ville'])
            ->where('statut', StatutAnnonce::EnModeration)
            ->oldest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $paiements = Commande::whereIn('annonce_id', $page->getCollection()->pluck('id'))
            ->where('statut', StatutCommande::Payee)
            ->whereNull('consommee_le')
            ->orderBy('payee_le')
            ->get()
            ->groupBy('annonce_id')
            ->map(fn ($commandes) => $commandes->first());

        $page->through(function (Annonce $a) use ($paiements) {
            $paiement = $paiements->get($a->id);

            return [
                'id' => $a->id,
                'titre' => $a->titre,
                'description' => $a->description,
                'type_bien' => $a->type_bien->libelle(),
                'type_offre' => $a->type_offre->libelle(),
                'prix' => $a->prix,
                'periodicite' => $a->periodicite?->libelle(),
                'caution' => $a->caution,
                'superficie' => $a->superficie,
                'nb_pieces' => $a->nb_pieces,
                'nb_chambres' => $a->nb_chambres,
                'nb_salles_bain' => $a->nb_salles_bain,
                'adresse_indicative' => $a->adresse_indicative,
                'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
                'bailleur' => [
                    'nom' => $a->user->name,
                    'email' => $a->user->email,
                    'telephone' => $a->user->telephone,
                ],
                'paiement' => $paiement ? [
                    'libelle' => $paiement->libelle,
                    'montant' => $paiement->montant,
                    'duree_jours' => $paiement->duree_jours,
                ] : null,
                'soumise_le' => $a->updated_at?->toIso8601String(),
            ];
        });

        return Inertia::render('Admin/Moderation/Index', ['annonces' => $page]);
    }

    public function valider(Request $request, Annonce $annonce, ModererAnnonce $service): RedirectResponse
    {
        try {
            $service->valider($annonce, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['moderation' => $e->getMessage()]);
        }

        return back();
    }

    public function rejeter(Request $request, Annonce $annonce, ModererAnnonce $service): RedirectResponse
    {
        $data = $request->validate([
            'motif_rejet' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $service->rejeter($annonce, $request->user(), $data['motif_rejet']);
        } catch (DomainException $e) {
            return back()->withErrors(['moderation' => $e->getMessage()]);
        }

        return back();
    }
}
