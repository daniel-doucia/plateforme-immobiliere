<?php

use App\Enums\Role;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Moderation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function compteSuppression(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function supprimerLeCompte($test, User $user, string $motDePasse = 'password')
{
    return $test->actingAs($user)->from('/profile')->delete('/profile', ['password' => $motDePasse]);
}

test('un administrateur ne peut pas supprimer son propre compte', function () {
    $admin = compteSuppression(Role::Admin);

    supprimerLeCompte($this, $admin)
        ->assertSessionHasErrors('compte')
        ->assertRedirect('/profile');

    expect(User::find($admin->id))->not->toBeNull();
    $this->assertAuthenticatedAs($admin);
});

test('un bailleur qui a des annonces ne peut pas supprimer son compte', function () {
    $bailleur = compteSuppression(Role::Bailleur);
    Annonce::factory()->create(['user_id' => $bailleur->id]);

    supprimerLeCompte($this, $bailleur)->assertSessionHasErrors('compte');

    expect(User::find($bailleur->id))->not->toBeNull();
});

test('une annonce supprimée compte aussi dans l\'historique', function () {
    $bailleur = compteSuppression(Role::Bailleur);
    Annonce::factory()->create(['user_id' => $bailleur->id])->delete();

    supprimerLeCompte($this, $bailleur)->assertSessionHasErrors('compte');

    expect(User::find($bailleur->id))->not->toBeNull();
});

test('un modérateur qui a pris des décisions ne peut pas supprimer son compte', function () {
    $moderateur = compteSuppression(Role::Moderateur);
    $annonce = Annonce::factory()->create();
    Moderation::create([
        'annonce_id' => $annonce->id,
        'moderateur_id' => $moderateur->id,
        'decision' => 'valide',
    ]);

    supprimerLeCompte($this, $moderateur)->assertSessionHasErrors('compte');

    expect(User::find($moderateur->id))->not->toBeNull();
});

test('un client sans historique peut supprimer son compte', function () {
    $client = compteSuppression(Role::Client);

    supprimerLeCompte($this, $client)->assertRedirect('/');

    $this->assertGuest();
    expect(User::find($client->id))->toBeNull();
});

test('la suppression d\'un bailleur sans annonce efface sa pièce d\'identité', function () {
    $bailleur = compteSuppression(Role::Bailleur);
    Storage::disk('local')->put('pieces-identite/piece.jpg', 'contenu');
    BailleurProfil::create([
        'user_id' => $bailleur->id,
        'piece_identite_path' => 'pieces-identite/piece.jpg',
    ]);

    supprimerLeCompte($this, $bailleur)->assertRedirect('/');

    expect(User::find($bailleur->id))->toBeNull()
        ->and(BailleurProfil::count())->toBe(0);
    Storage::disk('local')->assertMissing('pieces-identite/piece.jpg');
});

test('un mauvais mot de passe empêche la suppression', function () {
    $client = compteSuppression(Role::Client);

    supprimerLeCompte($this, $client, 'mauvais-mot-de-passe')
        ->assertSessionHasErrors('password');

    expect(User::find($client->id))->not->toBeNull();
});

test('un visiteur non connecté ne peut pas supprimer de compte', function () {
    $this->delete('/profile', ['password' => 'password'])->assertRedirect('/login');
});
