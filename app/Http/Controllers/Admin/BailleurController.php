<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\BailleurProfil;
use App\Models\User;
use App\Services\Statistiques\StatistiquesAnnonces;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BailleurController extends Controller
{
    public function index(Request $request, StatistiquesAnnonces $statistiques): Response
    {
        $recherche = mb_substr(trim($request->string('q')->toString()), 0, 100);

        $bailleurs = User::role(Role::Bailleur->value)
            ->when($recherche !== '', fn ($requete) => $requete->where(
                fn ($w) => $w->where('name', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%")
                    ->orWhere('telephone', 'like', "%{$recherche}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $ids = $bailleurs->getCollection()->pluck('id')->all();
        $stats = $statistiques->parBailleur($ids);
        $verifications = BailleurProfil::whereIn('user_id', $ids)
            ->get()
            ->mapWithKeys(fn (BailleurProfil $p) => [$p->user_id => $p->statut_verification->value]);

        $bailleurs->through(fn (User $u) => [
            'id' => $u->id,
            'nom' => $u->name,
            'email' => $u->email,
            'telephone' => $u->telephone,
            'verification' => $verifications->get($u->id),
            'desactive' => $u->desactive_le !== null,
            'inscrit_le' => $u->created_at?->toIso8601String(),
            'stats' => $stats[$u->id],
        ]);

        return Inertia::render('Admin/Bailleurs/Index', [
            'bailleurs' => $bailleurs,
            'recherche' => $recherche,
        ]);
    }
}
