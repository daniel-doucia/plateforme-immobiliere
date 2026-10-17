<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocalisationController;
use App\Http\Controllers\Admin\VerificationBailleurController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/verifications', [VerificationBailleurController::class, 'index'])->name('verifications.index');
        Route::get('/verifications/{profil}/piece', [VerificationBailleurController::class, 'piece'])->name('verifications.piece');
        Route::post('/verifications/{profil}/approuver', [VerificationBailleurController::class, 'approuver'])->name('verifications.approuver');
        Route::post('/verifications/{profil}/rejeter', [VerificationBailleurController::class, 'rejeter'])->name('verifications.rejeter');

        Route::get('/localisation', [LocalisationController::class, 'index'])->name('localisation.index');
        Route::post('/villes', [LocalisationController::class, 'storeVille'])->name('villes.store');
        Route::delete('/villes/{ville}', [LocalisationController::class, 'destroyVille'])->name('villes.destroy');
        Route::post('/villes/{ville}/quartiers', [LocalisationController::class, 'storeQuartier'])->name('quartiers.store');
        Route::patch('/quartiers/{quartier}', [LocalisationController::class, 'updateQuartier'])->name('quartiers.update');
        Route::delete('/quartiers/{quartier}', [LocalisationController::class, 'destroyQuartier'])->name('quartiers.destroy');
    });
