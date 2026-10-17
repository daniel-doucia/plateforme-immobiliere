<?php

use App\Models\BailleurProfil;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function donneesInscription(array $surcharge = []): array
{
    return array_merge([
        'name' => 'Test',
        'email' => 'test@example.com',
        'telephone' => '66 12 34 56',
        'type_compte' => 'client',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ], $surcharge);
}

test('la page d\'inscription s\'affiche', function () {
    $this->get('/register')->assertOk();
});

test('un client s\'inscrit avec le rôle client et un numéro normalisé', function () {
    $this->post('/register', donneesInscription())
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();

    $this->assertAuthenticated();
    expect($user->hasRole('client'))->toBeTrue()
        ->and($user->telephone)->toBe('+23566123456')
        ->and(BailleurProfil::where('user_id', $user->id)->exists())->toBeFalse();
});

test('un bailleur s\'inscrit avec un profil en attente de vérification', function () {
    $this->post('/register', donneesInscription(['type_compte' => 'bailleur']));

    $user = User::where('email', 'test@example.com')->first();
    $profil = BailleurProfil::where('user_id', $user->id)->first();

    expect($user->hasRole('bailleur'))->toBeTrue()
        ->and($profil->statut_verification->value)->toBe('en_attente');
});

test('on ne peut pas s\'inscrire comme administrateur', function () {
    $this->post('/register', donneesInscription(['type_compte' => 'admin']))
        ->assertSessionHasErrors('type_compte');

    $this->assertGuest();
});

test('un numéro invalide est refusé', function () {
    $this->post('/register', donneesInscription(['telephone' => '123']))
        ->assertSessionHasErrors('telephone');
});

test('un numéro déjà utilisé est refusé', function () {
    User::factory()->create(['telephone' => '+23566123456']);

    $this->post('/register', donneesInscription(['email' => 'autre@example.com']))
        ->assertSessionHasErrors('telephone');
});
