<?php

use App\Http\Controllers\Bailleur\AnnonceController;
use App\Http\Controllers\Bailleur\CommandeController;
use App\Http\Controllers\Bailleur\ProfilController;
use App\Http\Controllers\Bailleur\RenouvellementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:bailleur'])
    ->prefix('bailleur')
    ->name('bailleur.')
    ->group(function () {
        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
        Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');

        Route::get('/annonces', [AnnonceController::class, 'index'])->name('annonces.index');
        Route::get('/annonces/creer', [AnnonceController::class, 'create'])->name('annonces.create');
        Route::post('/annonces', [AnnonceController::class, 'store'])->name('annonces.store');
        Route::get('/annonces/{annonce}/modifier', [AnnonceController::class, 'edit'])->name('annonces.edit');
        Route::put('/annonces/{annonce}', [AnnonceController::class, 'update'])->name('annonces.update');
        Route::post('/annonces/{annonce}/soumettre', [AnnonceController::class, 'soumettre'])->name('annonces.soumettre');
        Route::post('/annonces/{annonce}/rouvrir', [AnnonceController::class, 'rouvrir'])->name('annonces.rouvrir');
        Route::post('/annonces/{annonce}/cloturer', [AnnonceController::class, 'cloturer'])->name('annonces.cloturer');
        Route::post('/annonces/{annonce}/renouveler', RenouvellementController::class)->name('annonces.renouveler');
        Route::delete('/annonces/{annonce}', [AnnonceController::class, 'destroy'])->name('annonces.destroy');

        Route::get('/annonces/{annonce}/forfait', [CommandeController::class, 'create'])->name('commandes.create');
        Route::post('/annonces/{annonce}/commandes', [CommandeController::class, 'store'])->name('commandes.store');
        Route::get('/commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
    });
