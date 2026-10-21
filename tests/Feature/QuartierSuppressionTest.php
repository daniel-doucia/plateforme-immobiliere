<?php

use App\Enums\Role;
use App\Models\Annonce;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function quartierSuppAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

test('un quartier utilisé par une annonce ne peut pas être supprimé', function () {
    $annonce = Annonce::factory()->create();

    $this->actingAs(quartierSuppAdmin())
        ->delete(route('admin.quartiers.destroy', $annonce->quartier_id))
        ->assertSessionHasErrors('quartier');

    expect(Quartier::find($annonce->quartier_id))->not->toBeNull();
});

test('une annonce supprimée retient aussi son quartier', function () {
    $annonce = Annonce::factory()->create();
    $annonce->delete();

    $this->actingAs(quartierSuppAdmin())
        ->delete(route('admin.quartiers.destroy', $annonce->quartier_id))
        ->assertSessionHasErrors('quartier');

    expect(Quartier::find($annonce->quartier_id))->not->toBeNull();
});

test('un quartier sans annonce peut être supprimé', function () {
    $ville = Ville::create(['nom' => 'Pala']);
    $quartier = Quartier::create(['ville_id' => $ville->id, 'nom' => 'Centre']);

    $this->actingAs(quartierSuppAdmin())
        ->delete(route('admin.quartiers.destroy', $quartier))
        ->assertSessionHasNoErrors();

    expect(Quartier::find($quartier->id))->toBeNull();
});
