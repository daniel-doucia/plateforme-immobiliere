<?php

namespace App\Http\Controllers;

use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use App\Models\Annonce;
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
            ->with('quartier.ville')
            ->when($f['type_offre'] ?? null, fn ($q, $v) => $q->where('type_offre', $v))
            ->when($f['type_bien'] ?? null, fn ($q, $v) => $q->where('type_bien', $v))
            ->when($f['quartier_id'] ?? null, fn ($q, $v) => $q->where('quartier_id', $v))
            ->when($f['ville_id'] ?? null, fn ($q, $v) => $q->whereHas('quartier', fn ($w) => $w->where('ville_id', $v)))
            ->when($f['prix_min'] ?? null, fn ($q, $v) => $q->where('prix', '>=', $v))
            ->when($f['prix_max'] ?? null, fn ($q, $v) => $q->where('prix', '<=', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('titre', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%")
            ));

        match ($f['tri'] ?? 'recent') {
            'prix_asc' => $requete->orderBy('prix'),
            'prix_desc' => $requete->orderByDesc('prix'),
            default => $requete->orderByDesc('publiee_le'),
        };

        $annonces = $requete
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Annonce $a) => $this->carte($a));

        return Inertia::render('Annonces/Index', [
            'annonces' => $annonces,
            'filtres' => [
                'q' => $f['q'] ?? '',
                'type_offre' => $f['type_offre'] ?? '',
                'type_bien' => $f['type_bien'] ?? '',
                'ville_id' => $f['ville_id'] ?? '',
                'quartier_id' => $f['quartier_id'] ?? '',
                'prix_min' => $f['prix_min'] ?? '',
                'prix_max' => $f['prix_max'] ?? '',
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
            ->with(['quartier.ville', 'user:id,name,telephone'])
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
                'lieu' => $annonce->quartier->nom.', '.$annonce->quartier->ville->nom,
                'publiee_le' => $annonce->publiee_le?->toIso8601String(),
            ],
            'bailleur' => ['nom' => $annonce->user->name],
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
            'tri' => ['nullable', 'in:recent,prix_asc,prix_desc'],
        ]);

        return $validateur->fails() ? [] : $validateur->validated();
    }

    private function carte(Annonce $a): array
    {
        return [
            'slug' => $a->slug,
            'titre' => $a->titre,
            'type_bien' => $a->type_bien->libelle(),
            'type_offre' => $a->type_offre->libelle(),
            'prix' => $a->prix,
            'periodicite' => $a->periodicite?->libelle(),
            'lieu' => $a->quartier->nom.', '.$a->quartier->ville->nom,
            'superficie' => $a->superficie,
            'nb_chambres' => $a->nb_chambres,
        ];
    }

    private function villes(): array
    {
        return Ville::with(['quartiers' => fn ($q) => $q->orderBy('nom')])
            ->orderBy('nom')
            ->get()
            ->filter(fn (Ville $v) => $v->quartiers->isNotEmpty())
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
