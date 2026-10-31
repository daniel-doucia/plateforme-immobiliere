<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function dcUtilisateur(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function dcDesactive(User $user): User
{
    $user->forceFill(['desactive_le' => now(), 'motif_desactivation' => 'Test'])->save();

    return $user;
}

test('l\'administrateur désactive un compte avec un motif', function () {
    $client = dcUtilisateur('client');

    $this->actingAs(dcUtilisateur('admin'))
        ->post(route('admin.utilisateurs.desactiver', $client), ['motif' => 'Annonces frauduleuses'])
        ->assertSessionHasNoErrors();

    $client->refresh();

    expect($client->desactive_le)->not->toBeNull()
        ->and($client->motif_desactivation)->toBe('Annonces frauduleuses')
        ->and($client->estDesactive())->toBeTrue();
});

test('le motif est facultatif mais limité en longueur', function () {
    $admin = dcUtilisateur('admin');
    $bailleur = dcUtilisateur('bailleur');

    $this->actingAs($admin)
        ->post(route('admin.utilisateurs.desactiver', $bailleur), ['motif' => str_repeat('a', 256)])
        ->assertSessionHasErrors('motif');

    expect($bailleur->fresh()->desactive_le)->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.utilisateurs.desactiver', $bailleur))
        ->assertSessionHasNoErrors();

    expect($bailleur->fresh()->desactive_le)->not->toBeNull();
});

test('l\'administrateur réactive un compte', function () {
    $client = dcDesactive(dcUtilisateur('client'));

    $this->actingAs(dcUtilisateur('admin'))
        ->post(route('admin.utilisateurs.reactiver', $client))
        ->assertSessionHasNoErrors();

    expect($client->fresh()->desactive_le)->toBeNull()
        ->and($client->fresh()->motif_desactivation)->toBeNull();
});

test('un compte désactivé ne peut pas se connecter', function () {
    $client = dcDesactive(dcUtilisateur('client'));

    $this->post(route('login'), ['email' => $client->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un compte réactivé peut de nouveau se connecter', function () {
    $client = dcDesactive(dcUtilisateur('client'));
    $client->forceFill(['desactive_le' => null])->save();

    $this->post(route('login'), ['email' => $client->email, 'password' => 'password'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($client);
});

test('un compte déjà connecté est déconnecté dès qu\'il est désactivé', function () {
    $client = dcUtilisateur('client');

    $this->actingAs($client)->get(route('dashboard'))->assertOk();

    dcDesactive($client);

    $this->actingAs($client->fresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('l\'administrateur ne peut pas se désactiver lui-même', function () {
    $admin = dcUtilisateur('admin');

    $this->actingAs($admin)
        ->post(route('admin.utilisateurs.desactiver', $admin))
        ->assertForbidden();

    expect($admin->fresh()->desactive_le)->toBeNull();
});

test('un administrateur ne peut pas en désactiver un autre', function () {
    $autre = dcUtilisateur('admin');

    $this->actingAs(dcUtilisateur('admin'))
        ->post(route('admin.utilisateurs.desactiver', $autre))
        ->assertForbidden();

    expect($autre->fresh()->desactive_le)->toBeNull();
});

test('les rôles non administrateurs n\'ont aucun accès', function (string $role) {
    $cible = dcUtilisateur('client');
    $utilisateur = dcUtilisateur($role);

    $this->actingAs($utilisateur)->get(route('admin.utilisateurs.index'))->assertForbidden();
    $this->actingAs($utilisateur)->post(route('admin.utilisateurs.desactiver', $cible))->assertForbidden();
    $this->actingAs($utilisateur)->post(route('admin.utilisateurs.reactiver', $cible))->assertForbidden();

    expect($cible->fresh()->desactive_le)->toBeNull();
})->with(['client', 'bailleur', 'moderateur']);

test('un visiteur est renvoyé vers la connexion', function () {
    $cible = dcUtilisateur('client');

    $this->get(route('admin.utilisateurs.index'))->assertRedirect(route('login'));
    $this->post(route('admin.utilisateurs.desactiver', $cible))->assertRedirect(route('login'));
});

test('la liste filtre les comptes désactivés', function () {
    $admin = dcUtilisateur('admin');
    dcUtilisateur('client');
    $desactive = dcDesactive(dcUtilisateur('bailleur'));

    $this->actingAs($admin)
        ->get(route('admin.utilisateurs.index', ['filtre' => 'desactives']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Utilisateurs/Index')
            ->where('filtre', 'desactives')
            ->has('utilisateurs.data', 1)
            ->where('utilisateurs.data.0.email', $desactive->email)
            ->where('utilisateurs.data.0.desactive', true)
        );
});

test('la liste signale les comptes qu\'on ne peut pas désactiver', function () {
    $admin = dcUtilisateur('admin');

    $this->actingAs($admin)
        ->get(route('admin.utilisateurs.index', ['q' => $admin->email]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('utilisateurs.data', 1)
            ->where('utilisateurs.data.0.gerable', false)
        );
});

test('les annonces d\'un bailleur désactivé disparaissent du site puis reviennent', function () {
    $bailleur = dcUtilisateur('bailleur');
    $annonce = \App\Models\Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);
    \App\Models\Annonce::factory()->publiee()->create(); // un autre bailleur, toujours actif

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 2));

    dcDesactive($bailleur);

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 1));
    $this->get(route('annonces.show', $annonce->slug))->assertNotFound();

    $bailleur->forceFill(['desactive_le' => null])->save();

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 2));
    $this->get(route('annonces.show', $annonce->slug))->assertOk();
});

test('la page des bailleurs signale un compte désactivé', function () {
    $bailleur = dcDesactive(dcUtilisateur('bailleur'));

    $this->actingAs(dcUtilisateur('admin'))
        ->get(route('admin.bailleurs.index', ['q' => $bailleur->email]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Bailleurs/Index')
            ->where('bailleurs.data.0.desactive', true)
        );
});
