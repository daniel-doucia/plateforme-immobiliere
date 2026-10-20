<?php

use App\Http\Controllers\Admin\BailleurController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ForfaitController;
use App\Http\Controllers\Admin\LocalisationController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\VerificationBailleurController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/bailleurs', [BailleurController::class, 'index'])->name('bailleurs.index');

        Route::get('/forfaits', [ForfaitController::class, 'index'])->name('forfaits.index');
        Route::post('/forfaits', [ForfaitController::class, 'store'])->name('forfaits.store');
        Route::patch('/forfaits/{forfait}', [ForfaitController::class, 'update'])->name('forfaits.update');
        Route::delete('/forfaits/{forfait}', [ForfaitController::class, 'destroy'])->name('forfaits.destroy');

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

Route::middleware(['auth', 'verified', 'permission:annonces.moderer'])
    ->prefix('admin/moderation')
    ->name('admin.moderation.')
    ->group(function () {
        Route::get('/', [ModerationController::class, 'index'])->name('index');
        Route::post('/{annonce}/valider', [ModerationController::class, 'valider'])->name('valider');
        Route::post('/{annonce}/rejeter', [ModerationController::class, 'rejeter'])->name('rejeter');
    });
