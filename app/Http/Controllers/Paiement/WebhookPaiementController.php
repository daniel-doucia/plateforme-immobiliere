<?php

namespace App\Http\Controllers\Paiement;

use App\Http\Controllers\Controller;
use App\Services\Paiement\FournisseurFactory;
use App\Services\Paiement\TraiterPaiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WebhookPaiementController extends Controller
{
    public function __invoke(
        Request $request,
        string $fournisseur,
        FournisseurFactory $fabrique,
        TraiterPaiement $service,
    ): JsonResponse {
        $adaptateur = $fabrique->pour($fournisseur);
        $corps = $request->getContent();

        if (! $adaptateur->signatureValide($corps, $request->header('X-Signature'))) {
            Log::warning('Webhook de paiement refusé : signature invalide', [
                'fournisseur' => $fournisseur,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Signature invalide.'], 403);
        }

        try {
            $evenement = $adaptateur->analyser($corps);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Événement invalide.'], 422);
        }

        return response()->json(['resultat' => $service->appliquer($adaptateur, $evenement)]);
    }
}
