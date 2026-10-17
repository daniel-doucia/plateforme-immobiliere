<?php

use App\Http\Controllers\Bailleur\ProfilController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:bailleur'])
    ->prefix('bailleur')
    ->name('bailleur.')
    ->group(function () {
        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
        Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');
    });
