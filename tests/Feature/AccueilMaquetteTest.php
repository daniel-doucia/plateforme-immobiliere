<?php

use App\Enums\Role;
use App\Enums\StatutVerification;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Favori;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function mqBailleur(bool $verifie): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Bailleur->value);

    BailleurProfil::create([
        'user_id' => $user->id,
        'piece_identite_path' => 'pieces-identite/x.jpg',
        'statut_verification' => $verifie ? StatutVerification::Verifie : StatutVerification::EnAttente,
    ]);

    return $user;
}

function mqClient(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Client->value);

    return $user;
}

test('la carte d\'annonce indique un bailleur vérifié', function () {
    Annonce::factory()->publiee()->create(['user_id' => mqBailleur(true)->id]);

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('annonces.data.0.verifie', true)
            ->where('annonces.data.0.publiee_le', fn ($valeur) => is_string($valeur))
            ->where('annonces.data.0.type_code', fn ($valeur) => is_string($valeur)));
});

test('la carte n\'affiche pas « vérifié » pour un bailleur non vérifié', function () {
    Annonce::factory()->publiee()->create(['user_id' => mqBailleur(false)->id]);

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.verifie', false));
});

test('un visiteur n\'a aucun favori sur les cartes', function () {
    Annonce::factory()->publiee()->create();

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.favori', false));
});

test('seuls les favoris de l\'utilisateur connecté sont marqués', function () {
    $moi = mqClient();
    $autre = mqClient();
    $annonce = Annonce::factory()->publiee()->create();

    Favori::create(['user_id' => $autre->id, 'annonce_id' => $annonce->id]);

    $this->actingAs($moi)->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.favori', false));

    Favori::create(['user_id' => $moi->id, 'annonce_id' => $annonce->id]);

    $this->actingAs($moi)->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.favori', true));
});

test('le filtre « chambres minimum » s\'applique toujours à la recherche publique', function () {
    Annonce::factory()->publiee()->create(['nb_chambres' => 1]);
    Annonce::factory()->publiee()->create(['nb_chambres' => 4]);

    $this->get(route('annonces.index', ['chambres_min' => 3]))
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 1));
});
