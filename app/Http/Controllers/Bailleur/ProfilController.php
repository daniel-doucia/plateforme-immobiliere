<?php

namespace App\Http\Controllers\Bailleur;

use App\Enums\StatutVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bailleur\UpdateProfilRequest;
use App\Models\BailleurProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfilController extends Controller
{
    public function edit(Request $request): Response
    {
        $profil = $this->profilDe($request);

        return Inertia::render('Bailleur/Profil', [
            'profil' => [
                'type' => $profil->type->value,
                'nom_commercial' => $profil->nom_commercial,
                'statut' => $profil->statut_verification->value,
                'motif_rejet' => $profil->motif_rejet,
                'a_piece' => filled($profil->piece_identite_path),
            ],
        ]);
    }

    public function update(UpdateProfilRequest $request): RedirectResponse
    {
        $profil = $this->profilDe($request);
        $data = $request->validated();

        $attributs = [
            'type' => $data['type'],
            'nom_commercial' => $data['nom_commercial'] ?? null,
        ];

        // Une nouvelle pièce remet toujours le dossier en attente de vérification
        if ($request->hasFile('piece_identite')) {
            if ($profil->piece_identite_path) {
                Storage::disk('local')->delete($profil->piece_identite_path);
            }

            $attributs['piece_identite_path'] = $request->file('piece_identite')
                ->store('pieces-identite', 'local');
            $attributs['statut_verification'] = StatutVerification::EnAttente;
            $attributs['motif_rejet'] = null;
            $attributs['verifie_par'] = null;
            $attributs['verifie_le'] = null;
        }

        $profil->update($attributs);

        return back();
    }

    private function profilDe(Request $request): BailleurProfil
    {
        return BailleurProfil::firstOrCreate(['user_id' => $request->user()->id])->fresh();
    }
}
