<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutVerification;
use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use App\Models\BailleurProfil;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['annonces.photo_obligatoire' => true]);
});

function bailleurVerifiePourPhoto(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Bailleur->value);

    BailleurProfil::create([
        'user_id' => $user->id,
        'piece_identite_path' => 'pieces-identite/x.jpg',
        'statut_verification' => StatutVerification::Verifie,
    ]);

    return $user;
}

function unePhotoPour(Annonce $annonce): AnnoncePhoto
{
    return AnnoncePhoto::create([
        'annonce_id' => $annonce->id,
        'chemin' => 'annonces/'.$annonce->id.'/test.png',
        'mime' => 'image/png',
        'taille' => 1000,
        'largeur' => 600,
        'hauteur' => 450,
        'ordre' => 1,
    ]);
}

test('une annonce sans photo ne peut pas être soumise', function () {
    $bailleur = bailleurVerifiePourPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.soumettre', $annonce))
        ->assertForbidden();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('une annonce avec une photo peut être soumise', function () {
    $bailleur = bailleurVerifiePourPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    unePhotoPour($annonce);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.soumettre', $annonce))
        ->assertRedirect();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement);
});

test('le bouton soumettre n\'est proposé qu\'avec une photo', function () {
    $bailleur = bailleurVerifiePourPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)->get(route('bailleur.annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.peut.soumettre', false));

    unePhotoPour($annonce);

    $this->actingAs($bailleur)->get(route('bailleur.annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.peut.soumettre', true));
});

test('la règle peut être désactivée par la configuration', function () {
    config(['annonces.photo_obligatoire' => false]);
    $bailleur = bailleurVerifiePourPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.soumettre', $annonce))
        ->assertRedirect();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement);
});

test('le modérateur voit les photos de l\'annonce à modérer', function () {
    $annonce = Annonce::factory()->create();
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);
    $annonce->transitionner(StatutAnnonce::EnModeration);
    unePhotoPour($annonce);

    $moderateur = User::factory()->create();
    $moderateur->assignRole(Role::Moderateur->value);

    $this->actingAs($moderateur)->get(route('admin.moderation.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('annonces.data.0.photos', 1)
            ->where('annonces.data.0.photos.0.url', fn ($url) => str_starts_with($url, '/photos/')));
});
