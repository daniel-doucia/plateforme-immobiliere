<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoPublicController extends Controller
{
    public function __invoke(Request $request, AnnoncePhoto $photo): StreamedResponse
    {
        $annonce = $photo->annonce; // null si l'annonce a été supprimée
        abort_if($annonce === null, 404);

        $publique = Annonce::publiees()->whereKey($annonce->id)->exists();
        $utilisateur = $request->user();

        $autorise = $publique
            || ($utilisateur !== null && ($utilisateur->id === $annonce->user_id || $utilisateur->can('annonces.moderer')));

        // 404 (et non 403) : on ne révèle pas l'existence d'une photo non publique
        abort_unless($autorise, 404);
        abort_unless(Storage::disk('local')->exists($photo->chemin), 404);

        return Storage::disk('local')->response($photo->chemin, null, [
            'Content-Type' => $photo->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $publique ? 'public, max-age=3600' : 'private, no-store',
        ]);
    }
}
