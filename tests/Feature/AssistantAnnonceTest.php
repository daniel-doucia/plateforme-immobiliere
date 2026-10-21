<?php

use App\Enums\Role;
use App\Enums\StatutVerification;
use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use App\Models\BailleurProfil;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['annonces.photo_obligatoire' => true]);
});

function assistBailleur(bool $verifie = true): User
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

function assistDonnees(array $surcharge = []): array
{
    $ville = Ville::firstOrCreate(['nom' => "N'Djaména"]);
    $quartier = Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => 'Moursal']);

    return array_merge([
        'quartier_id' => $quartier->id,
        'titre' => 'Belle chambre moderne à Moursal',
        'description' => str_repeat('Description détaillée du bien. ', 3),
        'type_bien' => 'chambre',
        'type_offre' => 'location',
        'prix' => 40000,
        'periodicite' => 'mensuel',
    ], $surcharge);
}

function assistPhoto(Annonce $annonce): AnnoncePhoto
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

test('créer une annonce mène directement à l\'étape des photos', function () {
    $bailleur = assistBailleur();

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.store'), assistDonnees())
        ->assertRedirect(route('bailleur.annonces.photos.index', Annonce::first()));

    expect(Annonce::count())->toBe(1);
});

test('modifier une annonce mène aussi à l\'étape des photos', function () {
    $bailleur = assistBailleur();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->put(route('bailleur.annonces.update', $annonce), assistDonnees())
        ->assertRedirect(route('bailleur.annonces.photos.index', $annonce));
});

test('le récapitulatif signale l\'absence de photo et empêche la soumission', function () {
    $bailleur = assistBailleur();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)->get(route('bailleur.annonces.recapitulatif', $annonce))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bailleur/Annonces/Recapitulatif')
            ->where('controles.photo', false)
            ->where('controles.compte_verifie', true)
            ->where('peut.soumettre', false));
});

test('le récapitulatif autorise la soumission quand tout est en ordre', function () {
    $bailleur = assistBailleur();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    assistPhoto($annonce);

    $this->actingAs($bailleur)->get(route('bailleur.annonces.recapitulatif', $annonce))
        ->assertInertia(fn (Assert $page) => $page
            ->where('controles.photo', true)
            ->where('controles.brouillon', true)
            ->where('peut.soumettre', true)
            ->has('photos', 1));
});

test('un compte non vérifié ne peut pas soumettre depuis le récapitulatif', function () {
    $bailleur = assistBailleur(false);
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    assistPhoto($annonce);

    $this->actingAs($bailleur)->get(route('bailleur.annonces.recapitulatif', $annonce))
        ->assertInertia(fn (Assert $page) => $page
            ->where('controles.compte_verifie', false)
            ->where('peut.soumettre', false));
});

test('après la soumission, le récapitulatif propose le paiement', function () {
    $bailleur = assistBailleur();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    assistPhoto($annonce);

    $this->actingAs($bailleur)->post(route('bailleur.annonces.soumettre', $annonce))->assertRedirect();

    $this->actingAs($bailleur)->get(route('bailleur.annonces.recapitulatif', $annonce))
        ->assertInertia(fn (Assert $page) => $page
            ->where('annonce.statut', 'en_attente_paiement')
            ->where('controles.brouillon', false));
});

test('le récapitulatif d\'un autre bailleur est interdit', function () {
    $annonce = Annonce::factory()->create(['user_id' => assistBailleur()->id]);

    $this->actingAs(assistBailleur())
        ->get(route('bailleur.annonces.recapitulatif', $annonce))
        ->assertForbidden();
});

test('un client n\'accède pas au récapitulatif', function () {
    $annonce = Annonce::factory()->create();
    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);

    $this->actingAs($client)->get(route('bailleur.annonces.recapitulatif', $annonce))->assertForbidden();
});

test('un visiteur est renvoyé vers la connexion', function () {
    $annonce = Annonce::factory()->create();

    $this->get(route('bailleur.annonces.recapitulatif', $annonce))->assertRedirect('/login');
});
