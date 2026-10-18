<?php

use App\Http\Controllers\Paiement\PaiementController;
use App\Http\Controllers\Paiement\PaiementFauxController;
use App\Http\Controllers\Paiement\WebhookPaiementController;
use Illuminate\Support\Facades\Route;

// Appelé par le fournisseur de paiement : pas de connexion, protégé par la signature
Route::post('/webhooks/paiement/{fournisseur}', WebhookPaiementController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.paiement');

Route::middleware(['auth', 'verified', 'role:bailleur'])
    ->prefix('paiement')
    ->name('paiement.')
    ->group(function () {
        Route::post('/commandes/{commande}/payer', [PaiementController::class, 'payer'])->name('payer');

        Route::get('/faux/{reference}', [PaiementFauxController::class, 'show'])->name('faux.show');
        Route::post('/faux/{reference}/simuler', [PaiementFauxController::class, 'simuler'])->name('faux.simuler');
    });
