<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TypeBien;
use App\Http\Controllers\Controller;
use App\Models\Forfait;
use App\Models\ForfaitModification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ForfaitController extends Controller
{
    public function index(): Response
    {
        $forfaits = Forfait::withCount('commandes')
            ->orderBy('type_bien')
            ->orderBy('duree_jours')
            ->get();

        $couverture = array_map(
            fn (TypeBien $type) => [
                'type' => $type->libelle(),
                'actifs' => $forfaits->filter(
                    fn (Forfait $f) => $f->actif && ($f->type_bien === null || $f->type_bien === $type)
                )->count(),
            ],
            TypeBien::cases()
        );

        $historique = ForfaitModification::with('user:id,name')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (ForfaitModification $m) => [
                'id' => $m->id,
                'forfait' => $m->forfait_nom,
                'utilisateur' => $m->user?->name ?? 'Compte supprimé',
                'date' => $m->created_at?->toIso8601String(),
                'resume' => $this->resume($m->avant, $m->apres),
            ]);

        return Inertia::render('Admin/Forfaits/Index', [
            'forfaits' => $forfaits->map(fn (Forfait $f) => [
                'id' => $f->id,
                'nom' => $f->nom,
                'type_bien' => $f->type_bien?->value,
                'duree_jours' => $f->duree_jours,
                'prix' => $f->prix,
                'actif' => $f->actif,
                'ordre' => $f->ordre,
                'commandes_count' => $f->commandes_count,
            ])->values(),
            'types' => array_map(fn (TypeBien $t) => ['value' => $t->value, 'label' => $t->libelle()], TypeBien::cases()),
            'couverture' => $couverture,
            'historique' => $historique,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);
        $this->verifierUnicite($data['type_bien'] ?? null, $data['duree_jours']);

        DB::transaction(function () use ($data, $request) {
            $forfait = Forfait::create([
                'nom' => $data['nom'],
                'type_bien' => $data['type_bien'] ?? null,
                'duree_jours' => $data['duree_jours'],
                'prix' => $data['prix'],
                'actif' => $data['actif'],
                'ordre' => $data['ordre'] ?? $data['duree_jours'],
            ]);

            $this->journaliser($forfait, $request->user()->id, null, $this->instantane($forfait->refresh()));
        });

        return back();
    }

    public function update(Request $request, Forfait $forfait): RedirectResponse
    {
        $data = $this->valider($request);
        $this->verifierUnicite($data['type_bien'] ?? null, $data['duree_jours'], $forfait->id);

        DB::transaction(function () use ($data, $request, $forfait) {
            $avant = $this->instantane($forfait);

            $forfait->update([
                'nom' => $data['nom'],
                'type_bien' => $data['type_bien'] ?? null,
                'duree_jours' => $data['duree_jours'],
                'prix' => $data['prix'],
                'actif' => $data['actif'],
                'ordre' => $data['ordre'] ?? $data['duree_jours'],
            ]);

            $apres = $this->instantane($forfait->refresh());

            // Pas de trace quand rien n'a changé
            if ($avant !== $apres) {
                $this->journaliser($forfait, $request->user()->id, $avant, $apres);
            }
        });

        return back();
    }

    public function destroy(Request $request, Forfait $forfait): RedirectResponse
    {
        if ($forfait->commandes()->exists()) {
            throw ValidationException::withMessages([
                'forfait' => 'Ce forfait a déjà été commandé : désactivez-le plutôt que de le supprimer, pour conserver l\'historique des paiements.',
            ]);
        }

        DB::transaction(function () use ($request, $forfait) {
            $this->journaliser($forfait, $request->user()->id, $this->instantane($forfait), null);
            $forfait->delete();
        });

        return back();
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'min:3', 'max:100'],
            'type_bien' => ['nullable', Rule::enum(TypeBien::class)],
            'duree_jours' => ['required', 'integer', 'between:1,365'],
            'prix' => ['required', 'integer', 'between:100,10000000'],
            'actif' => ['required', 'boolean'],
            'ordre' => ['nullable', 'integer', 'between:0,1000'],
        ], [], [
            'nom' => 'nom du forfait',
            'type_bien' => 'type de bien',
            'duree_jours' => 'durée',
            'prix' => 'prix',
            'actif' => 'état',
            'ordre' => 'ordre',
        ]);
    }

    private function verifierUnicite(?string $type, int $duree, ?int $ignorerId = null): void
    {
        $existe = Forfait::query()
            ->when($type === null, fn ($q) => $q->whereNull('type_bien'), fn ($q) => $q->where('type_bien', $type))
            ->where('duree_jours', $duree)
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'duree_jours' => 'Un forfait existe déjà pour ce type de bien et cette durée.',
            ]);
        }
    }

    /** Valeurs simples (jamais d'objets) pour comparer et journaliser. */
    private function instantane(Forfait $forfait): array
    {
        return [
            'nom' => $forfait->nom,
            'type_bien' => $forfait->type_bien?->value,
            'duree_jours' => $forfait->duree_jours,
            'prix' => $forfait->prix,
            'actif' => $forfait->actif,
            'ordre' => $forfait->ordre,
        ];
    }

    private function journaliser(Forfait $forfait, int $userId, ?array $avant, ?array $apres): void
    {
        ForfaitModification::create([
            'forfait_id' => $forfait->id,
            'forfait_nom' => $forfait->nom,
            'user_id' => $userId,
            'avant' => $avant,
            'apres' => $apres,
        ]);
    }

    private function resume(?array $avant, ?array $apres): string
    {
        if ($avant === null) {
            return 'Création : '.number_format($apres['prix'], 0, ',', ' ').' FCFA, '.$apres['duree_jours'].' jours';
        }

        if ($apres === null) {
            return 'Suppression du forfait';
        }

        $libelles = [
            'nom' => 'nom',
            'type_bien' => 'type de bien',
            'duree_jours' => 'durée',
            'prix' => 'prix',
            'actif' => 'état',
            'ordre' => 'ordre',
        ];

        $morceaux = [];
        foreach ($libelles as $cle => $libelle) {
            if (($avant[$cle] ?? null) === ($apres[$cle] ?? null)) {
                continue;
            }

            $morceaux[] = match ($cle) {
                'actif' => $apres[$cle] ? 'activé' : 'désactivé',
                'prix' => 'prix '.number_format($avant['prix'], 0, ',', ' ').' → '.number_format($apres['prix'], 0, ',', ' ').' FCFA',
                default => $libelle.' : '.($avant[$cle] ?? 'tous').' → '.($apres[$cle] ?? 'tous'),
            };
        }

        return implode(', ', $morceaux);
    }
}
