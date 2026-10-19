<?php

use App\Http\Controllers\AnnoncePubliqueController;
use App\Http\Controllers\PhotoPublicController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/annonces', [AnnoncePubliqueController::class, 'index'])->name('annonces.index');
    Route::get('/annonces/{slug}', [AnnoncePubliqueController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('annonces.show');
});

// Limite plus large : une page de résultats charge une douzaine d'images
Route::get('/photos/{photo}', PhotoPublicController::class)
    ->whereNumber('photo')
    ->middleware('throttle:600,1')
    ->name('photos.show');
