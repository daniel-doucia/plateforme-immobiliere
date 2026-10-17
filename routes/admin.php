<?php

use App\Http\Controllers\Admin\DashboardController;
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
    });
