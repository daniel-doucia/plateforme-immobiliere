<?php

namespace App\Http\Controllers\Bailleur;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PhotoController extends Controller
{
    /** Types réellement acceptés, d'après le contenu du fichier (jamais d'après son nom). */
    private const TYPES = [
        IMAGETYPE_JPEG => ['jpg', 'image/jpeg'],
        IMAGETYPE_PNG => ['png', 'image/png'],
        IMAGETYPE_WEBP => ['webp', 'image/webp'],
    ];

    public function index(Request $request, Annonce $annonce): Response
    {
        abort_unless($annonce->user_id === $request->user()->id, 403);

        return Inertia::render('Bailleur/Annonces/Photos', [
            'annonce' => [
                'id' => $annonce->id,
                'titre' => $annonce->titre,
                'statut_libelle' => $annonce->statut->libelle(),
            ],
            'photos' => $annonce->photos
                ->map(fn (AnnoncePhoto $p) => ['id' => $p->id, 'url' => $p->url()])
                ->values(),
            'max' => Annonce::MAX_PHOTOS,
            'peut_modifier' => Gate::allows('update', $annonce),
        ]);
    }

    public function store(Request $request, Annonce $annonce): RedirectResponse
    {
        Gate::authorize('update', $annonce);

        $restant = Annonce::MAX_PHOTOS - AnnoncePhoto::where('annonce_id', $annonce->id)->count();

        if ($restant <= 0) {
            throw ValidationException::withMessages([
                'photos' => 'Cette annonce a déjà le maximum de '.Annonce::MAX_PHOTOS.' photos.',
            ]);
        }

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.$restant],
            'photos.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
                'dimensions:min_width=400,min_height=300,max_width=8000,max_height=8000',
            ],
        ], [
            'photos.required' => 'Choisissez au moins une photo.',
            'photos.max' => 'Vous pouvez encore ajouter '.$restant.' photo(s) au maximum.',
            'photos.*.mimes' => 'Chaque photo doit être au format JPG, PNG ou WebP.',
            'photos.*.max' => 'Chaque photo doit peser 3 Mo au maximum.',
            'photos.*.dimensions' => 'Chaque photo doit être une vraie image d\'au moins 400 × 300 pixels.',
            'photos.*.uploaded' => 'Une photo n\'a pas pu être envoyée (trop lourde ou envoi interrompu).',
        ]);

        // Contrôle du contenu réel avant d'écrire quoi que ce soit
        $prets = [];
        foreach ($request->file('photos') as $fichier) {
            $info = @getimagesize($fichier->getRealPath());
            $type = is_array($info) ? $info[2] : null;

            if (! isset(self::TYPES[$type])) {
                throw ValidationException::withMessages(['photos' => 'Un des fichiers n\'est pas une image valide.']);
            }

            $prets[] = [$fichier, $info];
        }

        DB::transaction(function () use ($annonce, $prets) {
            $ordre = (int) AnnoncePhoto::where('annonce_id', $annonce->id)->max('ordre');

            foreach ($prets as [$fichier, $info]) {
                [$extension, $mime] = self::TYPES[$info[2]];

                // Nom aléatoire : le nom d'origine n'est jamais conservé
                $chemin = $fichier->storeAs('annonces/'.$annonce->id, Str::uuid().'.'.$extension, 'local');

                AnnoncePhoto::create([
                    'annonce_id' => $annonce->id,
                    'chemin' => $chemin,
                    'mime' => $mime,
                    'taille' => $fichier->getSize(),
                    'largeur' => $info[0],
                    'hauteur' => $info[1],
                    'ordre' => ++$ordre,
                ]);
            }
        });

        return back();
    }

    public function destroy(Annonce $annonce, AnnoncePhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $annonce);
        abort_unless($photo->annonce_id === $annonce->id, 404);

        $photo->delete();

        return back();
    }

    public function principale(Annonce $annonce, AnnoncePhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $annonce);
        abort_unless($photo->annonce_id === $annonce->id, 404);

        DB::transaction(function () use ($annonce, $photo) {
            $autres = $annonce->photos()->where('id', '!=', $photo->id)->get();

            $position = 1;
            $photo->update(['ordre' => $position++]);

            foreach ($autres as $autre) {
                $autre->update(['ordre' => $position++]);
            }
        });

        return back();
    }
}
