<?php

use App\Http\Controllers\AnnoncePubliqueController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Page d'accueil : la liste des annonces
Route::get('/', [AnnoncePubliqueController::class, 'index'])->name('accueil');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [CompteController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/bailleur.php';
require __DIR__.'/paiement.php';
require __DIR__.'/public.php';
require __DIR__.'/espace.php';
require __DIR__.'/contact.php';
require __DIR__.'/utilisateurs.php';
