<?php

use App\Enums\Role;
use App\Models\Annonce;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

test('le tableau de bord du bailleur liste ses cinq dernières annonces, et seulement les siennes', function () {
    $moi = User::factory()->create();
    $moi->assignRole(Role::Bailleur->value);

    Annonce::factory()->count(6)->create(['user_id' => $moi->id]);
    Annonce::factory()->count(3)->create(); // celles d'un autre bailleur

    $this->actingAs($moi)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('annonces_recentes', 5));
});

test('un client n\'a pas de liste d\'annonces récentes', function () {
    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);

    Annonce::factory()->count(2)->create();

    $this->actingAs($client)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('annonces_recentes', 0));
});
