<?php

use App\Http\Controllers\AlerteController;
use App\Http\Controllers\FavoriController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/favoris', [FavoriController::class, 'index'])->name('favoris.index');
    Route::post('/favoris/{slug}', [FavoriController::class, 'store'])
        ->where('slug', '[a-z0-9\-]+')
        ->middleware('throttle:60,1')
        ->name('favoris.store');
    Route::delete('/favoris/{slug}', [FavoriController::class, 'destroy'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('favoris.destroy');

    Route::get('/alertes', [AlerteController::class, 'index'])->name('alertes.index');
    Route::post('/alertes', [AlerteController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('alertes.store');
    Route::patch('/alertes/{alerte}', [AlerteController::class, 'toggle'])->whereNumber('alerte')->name('alertes.toggle');
    Route::delete('/alertes/{alerte}', [AlerteController::class, 'destroy'])->whereNumber('alerte')->name('alertes.destroy');
});
