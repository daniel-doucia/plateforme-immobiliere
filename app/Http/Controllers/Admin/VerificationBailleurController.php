<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutVerification;
use App\Http\Controllers\Controller;
use App\Models\BailleurProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationBailleurController extends Controller
{
    public function index(Request $request): Response
    {
        $statut = $request->string('statut', StatutVerification::EnAttente->value)->toString();

        if (! in_array($statut, array_column(StatutVerification::cases(), 'value'), true)) {
            $statut = StatutVerification::EnAttente->value;
        }

        $profils = BailleurProfil::with('user:id,name,email,telephone')
            ->where('statut_verification', $statut)
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (BailleurProfil $p) => [
                'id' => $p->id,
                'nom' => $p->user->name,
                'email' => $p->user->email,
                'telephone' => $p->user->telephone,
                'type' => $p->type->value,
                'nom_commercial' => $p->nom_commercial,
                'a_piece' => filled($p->piece_identite_path),
                'motif_rejet' => $p->motif_rejet,
                'cree_le' => $p->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Verifications/Index', [
            'profils' => $profils,
            'statut' => $statut,
        ]);
    }

    public function piece(BailleurProfil $profil): StreamedResponse
    {
        abort_unless(
            $profil->piece_identite_path && Storage::disk('local')->exists($profil->piece_identite_path),
            404
        );

        return Storage::disk('local')->response($profil->piece_identite_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function approuver(Request $request, BailleurProfil $profil): RedirectResponse
    {
        abort_if(blank($profil->piece_identite_path), 422, 'Aucune pièce d\'identité fournie.');

        $profil->update([
            'statut_verification' => StatutVerification::Verifie,
            'motif_rejet' => null,
            'verifie_par' => $request->user()->id,
            'verifie_le' => now(),
        ]);

        return back();
    }

    public function rejeter(Request $request, BailleurProfil $profil): RedirectResponse
    {
        $data = $request->validate([
            'motif_rejet' => ['required', 'string', 'max:500'],
        ]);

        $profil->update([
            'statut_verification' => StatutVerification::Rejete,
            'motif_rejet' => $data['motif_rejet'],
            'verifie_par' => $request->user()->id,
            'verifie_le' => now(),
        ]);

        return back();
    }
}
