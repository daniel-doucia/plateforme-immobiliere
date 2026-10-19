<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Commande;
use App\Models\Moderation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompteController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->hasRole(Role::Admin->value)) {
            $this->refuser('Un compte administrateur ne peut pas être supprimé : il faut au moins un administrateur pour gérer la plateforme.');
        }

        if ($this->aUnHistorique($user)) {
            $this->refuser('Ce compte a un historique (annonces, paiements ou décisions de modération) qui doit être conservé. Contactez-nous pour le fermer.');
        }

        $piece = BailleurProfil::where('user_id', $user->id)->value('piece_identite_path');

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // La pièce d'identité ne doit pas survivre au compte de son propriétaire
        if ($piece) {
            Storage::disk('local')->delete($piece);
        }

        return redirect('/');
    }

    /** Annonces supprimées comprises : leurs lignes existent toujours en base. */
    private function aUnHistorique(User $user): bool
    {
        return Annonce::withTrashed()->where('user_id', $user->id)->exists()
            || Commande::where('user_id', $user->id)->exists()
            || Moderation::where('moderateur_id', $user->id)->exists();
    }

    private function refuser(string $message): never
    {
        throw ValidationException::withMessages(['compte' => $message]);
    }
}
