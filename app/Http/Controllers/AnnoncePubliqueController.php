<?php

namespace App\Http\Controllers;

use App\Enums\StatutVerification;
use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use App\Models\BailleurProfil;
use App\Models\Favori;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AnnoncePubliqueController extends Controller
{
    public function index(Request $request): Response
    {
        $f = $this->filtres($request);

        $requete = Annonce::publiees()
            ->with(['quartier.ville', 'photos'])
            ->filtrer($f);

        match ($f['tri'] ?? 'recent') {
            'prix_asc' => $requete->orderBy('prix'),
            'prix_desc' => $requete->orderByDesc('prix'),
            default => $requete->orderByDesc('publiee_le'),
        };

        $page = $requete->orderByDesc('id')->paginate(12)->withQueryString();

        $annonces = $page->getCollection();

        // Bailleurs réellement vérifiés et favoris de l'utilisateur, pour les seules annonces affichées
        $verifies = BailleurProfil::whereIn('user_id', $annonces->pluck('user_id')->unique())
            ->where('statut_verification', StatutVerification::Verifie)
            ->pluck('user_id')
            ->flip();

        $favoris = $request->user()
            ? Favori::where('user_id', $request->user()->id)
                ->whereIn('annonce_id', $annonces->pluck('id'))
                ->pluck('annonce_id')
                ->flip()
            : collect();

        $page->through(fn (Annonce $a) => $this->carte($a, $verifies->has($a->user_id), $favoris->has($a->id)));

        return Inertia::render('Annonces/Index', [
            'annonces' => $page,
            'filtres' => [
                'q' => $f['q'] ?? '',
                'type_offre' => $f['type_offre'] ?? '',
                'type_bien' => $f['type_bien'] ?? '',
                'ville_id' => $f['ville_id'] ?? '',
                'quartier_id' => $f['quartier_id'] ?? '',
                'prix_min' => $f['prix_min'] ?? '',
                'prix_max' => $f['prix_max'] ?? '',
                'chambres_min' => $f['chambres_min'] ?? '',
                'tri' => $f['tri'] ?? 'recent',
            ],
            'villes' => $this->villes(),
            'types_bien' => $this->options(TypeBien::cases()),
            'types_offre' => $this->options(TypeOffre::cases()),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $annonce = Annonce::publiees()
            ->with(['quartier.ville', 'user:id,name,telephone', 'photos'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Compteur de vues sans toucher à updated_at ; les vues du propriétaire ne comptent pas
        if ($request->user()?->id !== $annonce->user_id) {
            DB::table('annonces')->where('id', $annonce->id)->increment('vues_count');
        }

        $peutVoirContact = $request->user() !== null;
        $telephone = $annonce->user->telephone;

        $contact = null;
        if ($peutVoirContact && filled($telephone)) {
            $message = 'Bonjour, je suis intéressé(e) par votre annonce « '.$annonce->titre.' ».';
            $contact = [
                'telephone' => $telephone,
                'whatsapp' => 'https://wa.me/'.ltrim($telephone, '+').'?text='.rawurlencode($message),
            ];
        }

        return Inertia::render('Annonces/Show', [
            'annonce' => [
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
                'hotel' => $annonce->detailsHotelPublics(),
                'lieu' => $annonce->quartier->nom.', '.$annonce->quartier->ville->nom,
                'publiee_le' => $annonce->publiee_le?->toIso8601String(),
            ],
            'photos' => $annonce->photos
                ->map(fn (AnnoncePhoto $p) => ['id' => $p->id, 'url' => $p->url()])
                ->values(),
            'bailleur' => ['nom' => $annonce->user->name],
            'slug' => $annonce->slug,
            'est_favori' => $request->user() !== null
                && Favori::where('user_id', $request->user()->id)->where('annonce_id', $annonce->id)->exists(),
            'peut_voir_contact' => $peutVoirContact,
            'contact' => $contact,
        ]);
    }

    /** Un paramètre invalide ignore l'ensemble des filtres plutôt que de provoquer une erreur. */
    private function filtres(Request $request): array
    {
        $validateur = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'type_offre' => ['nullable', Rule::enum(TypeOffre::class)],
            'type_bien' => ['nullable', Rule::enum(TypeBien::class)],
            'ville_id' => ['nullable', 'integer', 'min:1'],
            'quartier_id' => ['nullable', 'integer', 'min:1'],
            'prix_min' => ['nullable', 'integer', 'min:0'],
            'prix_max' => ['nullable', 'integer', 'min:0'],
            'chambres_min' => ['nullable', 'integer', 'between:1,20'],
            'tri' => ['nullable', 'in:recent,prix_asc,prix_desc'],
        ]);

        return $validateur->fails() ? [] : $validateur->validated();
    }

    private function carte(Annonce $a, bool $verifie, bool $favori): array
    {
        return [
            'id' => $a->id,
            'slug' => $a->slug,
            'titre' => $a->titre,
            'type_code' => $a->type_bien->value,
            'type_bien' => $a->type_bien->libelle(),
            'type_offre' => $a->type_offre->libelle(),
            'offre_code' => $a->type_offre->value,
            'prix' => $a->prix,
            'periodicite' => $a->periodicite?->libelle(),
            'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
            'ville' => $a->quartier->ville->nom,
            'quartier' => $a->quartier->nom,
            'superficie' => $a->superficie,
            'nb_chambres' => $a->nb_chambres,
            'nb_salles_bain' => $a->nb_salles_bain,
            'photo' => $a->photos->first()?->url(),
            'verifie' => $verifie,
            'favori' => $favori,
            'publiee_le' => $a->publiee_le?->toIso8601String(),
        ];
    }

    /** Toutes les villes, y compris celles qui n'ont pas encore d'annonce. */
    private function villes(): array
    {
        return Ville::with(['quartiers' => fn ($q) => $q->orderBy('nom')])
            ->orderBy('nom')
            ->get()
            ->map(fn (Ville $v) => [
                'id' => $v->id,
                'nom' => $v->nom,
                'quartiers' => $v->quartiers->map(fn ($q) => ['id' => $q->id, 'nom' => $q->nom])->values(),
            ])
            ->values()
            ->all();
    }

    private function options(array $cas): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->libelle()], $cas);
    }
}
