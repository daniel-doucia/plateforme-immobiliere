<?php

use App\Enums\Role;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function compteAvecRole(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

test('un client ne peut pas gérer la localisation', function () {
    $this->actingAs(compteAvecRole(Role::Client))
        ->get(route('admin.localisation.index'))
        ->assertForbidden();
});

test('l\'admin voit la page de localisation', function () {
    $this->actingAs(compteAvecRole(Role::Admin))
        ->get(route('admin.localisation.index'))
        ->assertOk();
});

test('l\'admin crée une ville', function () {
    $this->actingAs(compteAvecRole(Role::Admin))
        ->post(route('admin.villes.store'), ['nom' => 'Sarh'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('villes', ['nom' => 'Sarh', 'slug' => 'sarh']);
});

test('une ville en double (même sans accent) est refusée', function () {
    Ville::create(['nom' => 'Abéché']);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->post(route('admin.villes.store'), ['nom' => 'Abeche'])
        ->assertSessionHasErrors('nom');

    expect(Ville::count())->toBe(1);
});

test('l\'admin crée un quartier avec son arrondissement', function () {
    $ville = Ville::create(['nom' => "N'Djaména"]);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->post(route('admin.quartiers.store', $ville), ['nom' => 'Moursal', 'arrondissement' => 6])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('quartiers', ['ville_id' => $ville->id, 'nom' => 'Moursal', 'arrondissement' => 6]);
});

test('un quartier en double dans la même ville est refusé', function () {
    $ville = Ville::create(['nom' => 'Moundou']);
    Quartier::create(['ville_id' => $ville->id, 'nom' => 'Centre']);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->post(route('admin.quartiers.store', $ville), ['nom' => 'centre'])
        ->assertSessionHasErrors('nom');
});

test('un arrondissement invalide est refusé', function () {
    $ville = Ville::create(['nom' => 'Moundou']);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->post(route('admin.quartiers.store', $ville), ['nom' => 'Centre', 'arrondissement' => 99])
        ->assertSessionHasErrors('arrondissement');
});

test('l\'admin change l\'arrondissement d\'un quartier', function () {
    $ville = Ville::create(['nom' => "N'Djaména"]);
    $quartier = Quartier::create(['ville_id' => $ville->id, 'nom' => 'Farcha']);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->patch(route('admin.quartiers.update', $quartier), ['nom' => 'Farcha', 'arrondissement' => 2])
        ->assertSessionHasNoErrors();

    expect($quartier->fresh()->arrondissement)->toBe(2);
});

test('une ville qui contient des quartiers ne peut pas être supprimée', function () {
    $ville = Ville::create(['nom' => 'Moundou']);
    Quartier::create(['ville_id' => $ville->id, 'nom' => 'Centre']);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->delete(route('admin.villes.destroy', $ville))
        ->assertSessionHasErrors('ville');

    expect(Ville::find($ville->id))->not->toBeNull();
});

test('une ville vide peut être supprimée', function () {
    $ville = Ville::create(['nom' => 'Pala']);

    $this->actingAs(compteAvecRole(Role::Admin))
        ->delete(route('admin.villes.destroy', $ville))
        ->assertSessionHasNoErrors();

    expect(Ville::find($ville->id))->toBeNull();
});
