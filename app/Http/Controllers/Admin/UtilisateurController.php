<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UtilisateurController extends Controller
{
    private const FILTRES = ['tous', 'bailleurs', 'clients', 'desactives'];

    public function index(Request $request): Response
    {
        $recherche = mb_substr(trim($request->string('q')->toString()), 0, 100);
        $demande = $request->string('filtre')->toString();
        $filtre = in_array($demande, self::FILTRES, true) ? $demande : 'tous';

        $utilisateurs = User::with('roles:id,name')
            ->when($filtre === 'bailleurs', fn ($q) => $q->role(Role::Bailleur->value))
            ->when($filtre === 'clients', fn ($q) => $q->role(Role::Client->value))
            ->when($filtre === 'desactives', fn ($q) => $q->whereNotNull('desactive_le'))
            ->when($recherche !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%")
                    ->orWhere('telephone', 'like', "%{$recherche}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'nom' => $u->name,
                'email' => $u->email,
                'telephone' => $u->telephone,
                'roles' => $u->roles->pluck('name')->values(),
                'desactive' => $u->desactive_le !== null,
                'motif' => $u->motif_desactivation,
                'desactive_le' => $u->desactive_le?->toIso8601String(),
                'inscrit_le' => $u->created_at?->toIso8601String(),
                'gerable' => $u->id !== $request->user()->id && ! $u->hasRole(Role::Admin->value),
            ]);

        return Inertia::render('Admin/Utilisateurs/Index', [
            'utilisateurs' => $utilisateurs,
            'filtre' => $filtre,
            'recherche' => $recherche,
        ]);
    }

    public function desactiver(Request $request, User $utilisateur): RedirectResponse
    {
        $this->verifierGerable($request, $utilisateur);

        $donnees = $request->validate(['motif' => ['nullable', 'string', 'max:255']]);

        $utilisateur->forceFill([
            'desactive_le' => now(),
            'motif_desactivation' => $donnees['motif'] ?? null,
            // Invalide aussi les cookies « se souvenir de moi »
            'remember_token' => Str::random(60),
        ])->save();

        return back();
    }

    public function reactiver(Request $request, User $utilisateur): RedirectResponse
    {
        $this->verifierGerable($request, $utilisateur);

        $utilisateur->forceFill(['desactive_le' => null, 'motif_desactivation' => null])->save();

        return back();
    }

    private function verifierGerable(Request $request, User $utilisateur): void
    {
        abort_if($utilisateur->id === $request->user()->id, 403, 'Vous ne pouvez pas désactiver votre propre compte.');
        abort_if($utilisateur->hasRole(Role::Admin->value), 403, 'Un compte administrateur ne peut pas être désactivé.');
    }
}
