<?php

use App\Http\Controllers\AnnoncePubliqueController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/annonces', [AnnoncePubliqueController::class, 'index'])->name('annonces.index');
    Route::get('/annonces/{slug}', [AnnoncePubliqueController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('annonces.show');
});
