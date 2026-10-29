<?php

use App\Http\Controllers\Admin\MessageContactController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// Pages publiques
Route::get('/a-propos', [PageController::class, 'apropos'])
    ->middleware('throttle:120,1')
    ->name('apropos');

Route::get('/contact', [ContactController::class, 'create'])
    ->middleware('throttle:120,1')
    ->name('contact.create');

// 5 envois maximum par tranche de 10 minutes
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');

// Espace administrateur
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin/messages')
    ->name('admin.messages.')
    ->group(function () {
        Route::get('/', [MessageContactController::class, 'index'])->name('index');
        Route::patch('/{messageContact}/lu', [MessageContactController::class, 'basculerLu'])->name('lu');
        Route::delete('/{messageContact}', [MessageContactController::class, 'destroy'])->name('destroy');
    });
