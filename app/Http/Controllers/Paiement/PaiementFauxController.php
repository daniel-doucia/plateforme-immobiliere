<?php

namespace App\Http\Controllers\Paiement;

use App\Enums\StatutCommande;
use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\Paiement\FournisseurFactory;
use App\Services\Paiement\FournisseurFaux;
use App\Services\Paiement\TraiterPaiement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Page de paiement SIMULÉE, pour tester le circuit sans argent réel. */
class PaiementFauxController extends Controller
{
    public function show(string $reference): Response|RedirectResponse
    {
        $commande = $this->commande($reference);

        if ($commande->statut !== StatutCommande::EnAttente) {
            return redirect()->route('bailleur.commandes.show', $commande);
        }

        return Inertia::render('Paiement/Faux', [
            'reference' => $commande->reference,
            'montant' => $commande->montant,
            'libelle' => $commande->libelle,
            'annonce_titre' => $commande->annonce->titre,
        ]);
    }

    public function simuler(Request $request, string $reference): RedirectResponse
    {
        $commande = $this->commande($reference);
        $data = $request->validate(['resultat' => ['required', 'in:reussi,echec']]);

        // On joue le rôle du fournisseur : message signé envoyé au vrai webhook
        $fournisseur = new FournisseurFaux();
        $corps = json_encode([
            'event_id' => 'evt_'.Str::uuid(),
            'reference' => $commande->reference,
            'montant' => $commande->montant,
            'statut' => $data['resultat'],
        ]);

        $appel = Request::create(
            route('webhooks.paiement', 'faux'),
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $fournisseur->signer($corps)],
            $corps,
        );

        try {
            $reponse = app(WebhookPaiementController::class)(
                $appel,
                'faux',
                app(FournisseurFactory::class),
                app(TraiterPaiement::class),
            );
            $ok = $reponse->getStatusCode() === 200;
        } catch (HttpExceptionInterface) {
            $ok = false;
        }

        if (! $ok) {
            return back()->withErrors([
                'paiement' => 'Le paiement de test a été refusé. Vérifiez PAIEMENT_FAUX_SECRET dans le fichier .env.',
            ]);
        }

        return redirect()->route('bailleur.commandes.show', $commande);
    }

    private function commande(string $reference): Commande
    {
        abort_if(app()->isProduction(), 404);

        $commande = Commande::with('annonce:id,titre')->where('reference', $reference)->firstOrFail();
        Gate::authorize('view', $commande);

        return $commande;
    }
}
