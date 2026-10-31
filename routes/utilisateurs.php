<?php

use App\Http\Controllers\Admin\UtilisateurController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin/utilisateurs')
    ->name('admin.utilisateurs.')
    ->group(function () {
        Route::get('/', [UtilisateurController::class, 'index'])->name('index');
        Route::post('/{utilisateur}/desactiver', [UtilisateurController::class, 'desactiver'])
            ->whereNumber('utilisateur')->name('desactiver');
        Route::post('/{utilisateur}/reactiver', [UtilisateurController::class, 'reactiver'])
            ->whereNumber('utilisateur')->name('reactiver');
    });
