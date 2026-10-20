<?php

namespace App\Http\Controllers;

use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use App\Models\AlerteRecherche;
use App\Models\Quartier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AlerteController extends Controller
{
    private const CRITERES = ['q', 'type_offre', 'type_bien', 'ville_id', 'quartier_id', 'prix_min', 'prix_max'];

    public function index(Request $request): Response
    {
        $alertes = AlerteRecherche::with(['ville', 'quartier.ville'])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return Inertia::render('Alertes/Index', [
            'alertes' => $alertes->map(fn (AlerteRecherche $a) => [
                'id' => $a->id,
                'resume' => $a->resume(),
                'actif' => $a->actif,
                'creee_le' => $a->created_at?->toIso8601String(),
            ])->values(),
            'max' => (int) config('annonces.max_alertes'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type_offre' => ['nullable', Rule::enum(TypeOffre::class)],
            'type_bien' => ['nullable', Rule::enum(TypeBien::class)],
            'ville_id' => ['nullable', 'integer', Rule::exists('villes', 'id')],
            'quartier_id' => ['nullable', 'integer', Rule::exists('quartiers', 'id')],
            'prix_min' => ['nullable', 'integer', 'min:1', 'max:100000000000'],
            'prix_max' => ['nullable', 'integer', 'min:1', 'max:100000000000'],
        ]);

        $criteres = array_merge(array_fill_keys(self::CRITERES, null), $data);

        foreach (['ville_id', 'quartier_id', 'prix_min', 'prix_max'] as $champ) {
            if ($criteres[$champ] !== null) {
                $criteres[$champ] = (int) $criteres[$champ];
            }
        }

        if ($criteres['prix_min'] !== null && $criteres['prix_max'] !== null && $criteres['prix_min'] > $criteres['prix_max']) {
            throw ValidationException::withMessages(['prix_max' => 'Le prix maximum doit être supérieur au prix minimum.']);
        }

        if ($criteres['quartier_id'] !== null) {
            $quartier = Quartier::findOrFail($criteres['quartier_id']);

            if ($criteres['ville_id'] !== null && $criteres['ville_id'] !== $quartier->ville_id) {
                throw ValidationException::withMessages(['quartier_id' => "Ce quartier n'appartient pas à la ville choisie."]);
            }

            $criteres['ville_id'] = $quartier->ville_id;
        }

        if (count(array_filter($criteres, fn ($valeur) => $valeur !== null)) === 0) {
            throw ValidationException::withMessages([
                'alerte' => 'Choisissez au moins un critère (type, lieu, prix ou mot-clé) pour créer une alerte.',
            ]);
        }

        $userId = $request->user()->id;

        // Une alerte identique existe déjà : rien à créer
        $doublon = AlerteRecherche::where('user_id', $userId);
        foreach ($criteres as $champ => $valeur) {
            $valeur === null ? $doublon->whereNull($champ) : $doublon->where($champ, $valeur);
        }

        if ($doublon->exists()) {
            return redirect()->route('alertes.index');
        }

        $limite = (int) config('annonces.max_alertes');

        if (AlerteRecherche::where('user_id', $userId)->count() >= $limite) {
            throw ValidationException::withMessages([
                'alerte' => "Vous avez déjà {$limite} alertes : supprimez-en une avant d'en créer une nouvelle.",
            ]);
        }

        // Seules les annonces publiées à partir de maintenant déclencheront un e-mail
        AlerteRecherche::create($criteres + [
            'user_id' => $userId,
            'actif' => true,
            'derniere_verification_le' => now(),
        ]);

        return redirect()->route('alertes.index');
    }

    public function toggle(Request $request, int $alerte): RedirectResponse
    {
        $alerte = AlerteRecherche::where('user_id', $request->user()->id)->findOrFail($alerte);

        $actif = ! $alerte->actif;
        $changements = ['actif' => $actif];

        // À la réactivation, on repart de maintenant : pas de rafale de rattrapage
        if ($actif) {
            $changements['derniere_verification_le'] = now();
        }

        $alerte->update($changements);

        return back();
    }

    public function destroy(Request $request, int $alerte): RedirectResponse
    {
        AlerteRecherche::where('user_id', $request->user()->id)->findOrFail($alerte)->delete();

        return back();
    }
}
