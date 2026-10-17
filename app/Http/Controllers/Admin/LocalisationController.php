<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\Ville;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LocalisationController extends Controller
{
    public function index(): Response
    {
        $villes = Ville::with(['quartiers' => fn ($q) => $q->orderBy('arrondissement')->orderBy('nom')])
            ->orderBy('nom')
            ->get()
            ->map(fn (Ville $v) => [
                'id' => $v->id,
                'nom' => $v->nom,
                'quartiers' => $v->quartiers->map(fn (Quartier $q) => [
                    'id' => $q->id,
                    'nom' => $q->nom,
                    'arrondissement' => $q->arrondissement,
                ])->values(),
            ]);

        return Inertia::render('Admin/Localisation/Index', ['villes' => $villes]);
    }

    public function storeVille(Request $request): RedirectResponse
    {
        $data = $request->validate(['nom' => ['required', 'string', 'max:100']]);

        $existe = Ville::where('slug', Str::slug($data['nom']))->exists();
        if ($existe) {
            throw ValidationException::withMessages(['nom' => 'Cette ville existe déjà.']);
        }

        Ville::create($data);

        return back();
    }

    public function destroyVille(Ville $ville): RedirectResponse
    {
        if ($ville->quartiers()->exists()) {
            throw ValidationException::withMessages([
                'ville' => 'Supprimez d\'abord les quartiers de « '.$ville->nom.' ».',
            ]);
        }

        $ville->delete();

        return back();
    }

    public function storeQuartier(Request $request, Ville $ville): RedirectResponse
    {
        $data = $this->donneesQuartier($request);
        $this->verifierQuartierUnique($ville, $data['nom']);

        $ville->quartiers()->create($data);

        return back();
    }

    public function updateQuartier(Request $request, Quartier $quartier): RedirectResponse
    {
        $data = $this->donneesQuartier($request);
        $this->verifierQuartierUnique($quartier->ville, $data['nom'], $quartier->id);

        $quartier->update($data);

        return back();
    }

    public function destroyQuartier(Quartier $quartier): RedirectResponse
    {
        $quartier->delete();

        return back();
    }

    private function donneesQuartier(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'arrondissement' => ['nullable', 'integer', 'between:1,50'],
        ]);
    }

    private function verifierQuartierUnique(Ville $ville, string $nom, ?int $ignorerId = null): void
    {
        $existe = $ville->quartiers()
            ->where('slug', Str::slug($nom))
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages(['nom' => 'Ce quartier existe déjà dans cette ville.']);
        }
    }
}
