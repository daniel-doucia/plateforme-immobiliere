<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AlerteRecherche;
use App\Models\Annonce;
use App\Models\Favori;
use App\Models\User;
use App\Services\Statistiques\StatistiquesAnnonces;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StatistiquesAnnonces $statistiques): Response
    {
        $utilisateur = $request->user();
        $estBailleur = $utilisateur->hasRole(Role::Bailleur->value);

        // Chacun ne voit que ses propres chiffres et ses propres annonces
        return Inertia::render('Dashboard', [
            'stats' => $estBailleur
                ? $statistiques->parBailleur([$utilisateur->id])[$utilisateur->id]
                : null,
            'annonces_recentes' => $estBailleur ? $this->recentes($utilisateur) : [],
            'vues' => $estBailleur ? (int) Annonce::where('user_id', $utilisateur->id)->sum('vues_count') : 0,
            'favoris_recus' => $estBailleur
                ? Favori::whereIn('annonce_id', Annonce::where('user_id', $utilisateur->id)->select('id'))->count()
                : 0,
            'peut_creer' => $estBailleur && $utilisateur->can('create', Annonce::class),
            'favoris_count' => Favori::where('user_id', $utilisateur->id)->count(),
            'alertes_count' => AlerteRecherche::where('user_id', $utilisateur->id)->where('actif', true)->count(),
            'recommandees' => $estBailleur ? [] : $this->recommandees($utilisateur),
        ]);
    }

    private function recentes(User $utilisateur): array
    {
        return Annonce::with(['quartier.ville', 'photos'])
            ->where('user_id', $utilisateur->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Annonce $a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'statut' => $a->statut->value,
                'statut_libelle' => $a->statut->libelle(),
                'type_bien' => $a->type_bien->libelle(),
                'prix' => $a->prix,
                'periodicite' => $a->periodicite?->libelle(),
                'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
                'photo' => $a->photos->first()?->url(),
                'slug' => $a->estPubliee() ? $a->slug : null,
                'peut_modifier' => Gate::allows('update', $a),
            ])
            ->values()
            ->all();
    }

    /** Annonces publiées proches des favoris (même type de bien), hors favoris et hors ses propres annonces. */
    private function recommandees(User $utilisateur): array
    {
        $dejaFavoris = Favori::where('user_id', $utilisateur->id)->pluck('annonce_id');

        $types = DB::table('annonces')
            ->whereIn('id', $dejaFavoris)
            ->distinct()
            ->pluck('type_bien');

        $base = fn () => Annonce::publiees()
            ->with(['quartier.ville', 'photos'])
            ->where('user_id', '!=', $utilisateur->id)
            ->whereNotIn('id', $dejaFavoris)
            ->orderByDesc('publiee_le')
            ->orderByDesc('id')
            ->limit(3);

        $liste = $types->isNotEmpty() ? $base()->whereIn('type_bien', $types)->get() : collect();

        // Rien dans ses types habituels (ou aucun favori) : les plus récentes
        if ($liste->isEmpty()) {
            $liste = $base()->get();
        }

        return $liste
            ->map(fn (Annonce $a) => [
                'slug' => $a->slug,
                'titre' => $a->titre,
                'type_offre' => $a->type_offre->libelle(),
                'type_bien' => $a->type_bien->libelle(),
                'prix' => $a->prix,
                'periodicite' => $a->periodicite?->libelle(),
                'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
                'photo' => $a->photos->first()?->url(),
            ])
            ->values()
            ->all();
    }
}
