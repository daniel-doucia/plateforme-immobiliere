<?php

use App\Enums\TypeBailleur;
use App\Models\BailleurProfil;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function inscDonnees(array $surcharge = []): array
{
    return array_merge([
        'name' => 'Test Bailleur',
        'email' => 'bailleur@example.com',
        'telephone' => '66 12 34 56',
        'type_compte' => 'bailleur',
        'role_bailleur' => 'particulier',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ], $surcharge);
}

function inscProfil(): ?BailleurProfil
{
    $user = User::where('email', 'bailleur@example.com')->first();

    return $user ? BailleurProfil::where('user_id', $user->id)->first() : null;
}

test('un bailleur particulier est enregistré sans nom commercial', function () {
    $this->post('/register', inscDonnees())->assertSessionHasNoErrors();

    $profil = inscProfil();

    expect($profil->type)->toBe(TypeBailleur::Particulier)
        ->and($profil->nom_commercial)->toBeNull();
});

test('une agence ou un hôtel est enregistré avec son nom commercial', function () {
    $this->post('/register', inscDonnees(['role_bailleur' => 'agence', 'nom_commercial' => 'Hôtel Test']))
        ->assertSessionHasNoErrors();

    $profil = inscProfil();

    expect($profil->type)->toBe(TypeBailleur::Agence)
        ->and($profil->nom_commercial)->toBe('Hôtel Test');
});

test('une agence sans nom commercial est refusée', function () {
    $this->post('/register', inscDonnees(['role_bailleur' => 'agence']))
        ->assertSessionHasErrors(['nom_commercial' => "Le nom de l'agence ou de l'hôtel est obligatoire."]);

    expect(User::where('email', 'bailleur@example.com')->exists())->toBeFalse();
});

test('sans rôle indiqué, le bailleur est un particulier', function () {
    $donnees = inscDonnees();
    unset($donnees['role_bailleur']);

    $this->post('/register', $donnees)->assertSessionHasNoErrors();

    expect(inscProfil()->type)->toBe(TypeBailleur::Particulier);
});

test('un rôle inconnu est refusé', function () {
    $this->post('/register', inscDonnees(['role_bailleur' => 'administrateur']))
        ->assertSessionHasErrors('role_bailleur');

    expect(User::where('email', 'bailleur@example.com')->exists())->toBeFalse();
});

test('un client n\'a pas de profil bailleur, même s\'il envoie un rôle', function () {
    $this->post('/register', inscDonnees(['type_compte' => 'client', 'role_bailleur' => 'agence']))
        ->assertSessionHasNoErrors();

    $user = User::where('email', 'bailleur@example.com')->first();

    expect($user->hasRole('client'))->toBeTrue()
        ->and(BailleurProfil::where('user_id', $user->id)->exists())->toBeFalse();
});
