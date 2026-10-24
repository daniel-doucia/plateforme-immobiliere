<?php

use App\Enums\Role;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\Forfait;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function trfAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

test('la page des tarifs expose le nombre maximum de photos et le nombre de commandes', function () {
    Forfait::create([
        'nom' => 'Chambre · 30 jours',
        'type_bien' => TypeBien::Chambre,
        'duree_jours' => 30,
        'prix' => 2000,
        'actif' => true,
        'ordre' => 30,
    ]);

    $this->actingAs(trfAdmin())->get(route('admin.forfaits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('max_photos', Annonce::MAX_PHOTOS)
            ->where('forfaits.0.commandes_count', 0)
            ->where('forfaits.0.type_bien', 'chambre'));
});

test('un forfait valable pour tous les biens n\'a pas de type de bien', function () {
    Forfait::create([
        'nom' => 'Forfait général',
        'type_bien' => null,
        'duree_jours' => 15,
        'prix' => 1500,
        'actif' => true,
        'ordre' => 15,
    ]);

    $this->actingAs(trfAdmin())->get(route('admin.forfaits.index'))
        ->assertInertia(fn (Assert $page) => $page->where('forfaits.0.type_bien', null));
});
