<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Favori;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FavoriController extends Controller
{
    public function index(Request $request): Response
    {
        $favoris = Favori::with(['annonce.quartier.ville', 'annonce.photos'])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(function (Favori $favori) {
                $a = $favori->annonce;

                // Annonce expirée, clôturée ou supprimée : on n'affiche que son titre
                if ($a === null || ! $a->estPubliee()) {
                    return [
                        'id' => $favori->id,
                        'slug' => $a?->slug,
                        'titre' => $a?->titre ?? 'Annonce supprimée',
                        'disponible' => false,
                    ];
                }

                return [
                    'id' => $favori->id,
                    'slug' => $a->slug,
                    'titre' => $a->titre,
                    'disponible' => true,
                    'type_bien' => $a->type_bien->libelle(),
                    'type_offre' => $a->type_offre->libelle(),
                    'prix' => $a->prix,
                    'periodicite' => $a->periodicite?->libelle(),
                    'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
                    'photo' => $a->photos->first()?->url(),
                ];
            });

        return Inertia::render('Favoris/Index', ['favoris' => $favoris]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        // Seule une annonce publiée peut être mise en favori (sinon 404)
        $annonce = Annonce::publiees()->where('slug', $slug)->firstOrFail();
        $userId = $request->user()->id;

        $existe = Favori::where('user_id', $userId)->where('annonce_id', $annonce->id)->exists();

        if (! $existe) {
            $limite = (int) config('annonces.max_favoris');

            if (Favori::where('user_id', $userId)->count() >= $limite) {
                throw ValidationException::withMessages([
                    'favori' => "Vous avez atteint la limite de {$limite} favoris : retirez-en un avant d'en ajouter.",
                ]);
            }

            try {
                Favori::create(['user_id' => $userId, 'annonce_id' => $annonce->id]);
            } catch (UniqueConstraintViolationException) {
                // Double clic simultané : le favori existe déjà, c'est le résultat voulu
            }
        }

        return back();
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $annonce = Annonce::withTrashed()->where('slug', $slug)->firstOrFail();

        // Limité aux favoris de l'utilisateur connecté
        Favori::where('user_id', $request->user()->id)
            ->where('annonce_id', $annonce->id)
            ->delete();

        return back();
    }
}
