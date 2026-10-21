<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutVerification;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function bailleurDeTest(bool $verifie = true): User
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

function quartierDeTest(): Quartier
{
    $ville = Ville::firstOrCreate(['nom' => "N'Djaména"]);

    return Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => 'Moursal']);
}

function donneesAnnonce(array $surcharge = []): array
{
    return array_merge([
        'quartier_id' => quartierDeTest()->id,
        'titre' => 'Belle maison à louer à Moursal',
        'description' => str_repeat('Description détaillée du bien. ', 3),
        'type_bien' => 'maison',
        'type_offre' => 'location',
        'prix' => 150000,
        'periodicite' => 'mensuel',
    ], $surcharge);
}

test('un client ne peut pas accéder aux annonces bailleur', function () {
    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);

    $this->actingAs($client)->get(route('bailleur.annonces.index'))->assertForbidden();
});

test('un bailleur ne voit que ses propres annonces', function () {
    $moi = bailleurDeTest();
    $autre = bailleurDeTest();
    Annonce::factory()->create(['user_id' => $moi->id]);
    Annonce::factory()->count(2)->create(['user_id' => $autre->id]);

    $this->actingAs($moi)->get(route('bailleur.annonces.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bailleur/Annonces/Index')
            ->has('annonces.data', 1));
});

test('un bailleur non vérifié ne peut pas créer d\'annonce', function () {
    $bailleur = bailleurDeTest(false);

    $this->actingAs($bailleur)->get(route('bailleur.annonces.create'))->assertForbidden();
    $this->actingAs($bailleur)->post(route('bailleur.annonces.store'), donneesAnnonce())->assertForbidden();

    expect(Annonce::count())->toBe(0);
});

test('un bailleur vérifié crée un brouillon et ne peut pas imposer le statut ni le propriétaire', function () {
    $bailleur = bailleurDeTest();

    $this->actingAs($bailleur)->post(route('bailleur.annonces.store'), donneesAnnonce([
        'statut' => 'publiee',
        'user_id' => 999999,
    ]))->assertRedirectContains('/photos');

    $annonce = Annonce::first();

    expect($annonce->statut)->toBe(StatutAnnonce::Brouillon)
        ->and($annonce->user_id)->toBe($bailleur->id);
});

test('pour une vente, la périodicité et la caution sont ignorées', function () {
    $this->actingAs(bailleurDeTest())->post(route('bailleur.annonces.store'), donneesAnnonce([
        'type_offre' => 'vente',
        'periodicite' => 'mensuel',
        'caution' => 50000,
    ]));

    $annonce = Annonce::first();

    expect($annonce->periodicite)->toBeNull()->and($annonce->caution)->toBeNull();
});

test('une location sans périodicité est refusée', function () {
    $this->actingAs(bailleurDeTest())
        ->post(route('bailleur.annonces.store'), donneesAnnonce(['periodicite' => null]))
        ->assertSessionHasErrors('periodicite');
});

test('un prix invalide est refusé', function () {
    $this->actingAs(bailleurDeTest())
        ->post(route('bailleur.annonces.store'), donneesAnnonce(['prix' => -5]))
        ->assertSessionHasErrors('prix');
});

test('un bailleur ne peut pas modifier l\'annonce d\'un autre', function () {
    $autre = bailleurDeTest();
    $annonce = Annonce::factory()->create(['user_id' => $autre->id]);

    $this->actingAs(bailleurDeTest())
        ->put(route('bailleur.annonces.update', $annonce), donneesAnnonce())
        ->assertForbidden();

    $this->actingAs(bailleurDeTest())
        ->get(route('bailleur.annonces.edit', $annonce))
        ->assertForbidden();
});

test('un bailleur modifie son brouillon', function () {
    $bailleur = bailleurDeTest();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->put(route('bailleur.annonces.update', $annonce), donneesAnnonce(['prix' => 200000]))
        ->assertRedirectContains('/photos');

    expect($annonce->fresh()->prix)->toBe(200000);
});

test('une annonce qui n\'est plus un brouillon n\'est plus modifiable', function () {
    $bailleur = bailleurDeTest();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

    $this->actingAs($bailleur)
        ->put(route('bailleur.annonces.update', $annonce), donneesAnnonce())
        ->assertForbidden();
});

test('soumettre un brouillon le passe en attente de paiement', function () {
    $bailleur = bailleurDeTest();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)->post(route('bailleur.annonces.soumettre', $annonce))->assertRedirect();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement);
});

test('on ne peut pas soumettre l\'annonce d\'un autre', function () {
    $annonce = Annonce::factory()->create(['user_id' => bailleurDeTest()->id]);

    $this->actingAs(bailleurDeTest())
        ->post(route('bailleur.annonces.soumettre', $annonce))
        ->assertForbidden();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('une annonce rejetée peut être rouverte pour correction', function () {
    $bailleur = bailleurDeTest();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $annonce->forceFill(['statut' => StatutAnnonce::Rejetee])->save();

    $this->actingAs($bailleur)->post(route('bailleur.annonces.rouvrir', $annonce))->assertRedirect();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('un bailleur peut clôturer son annonce', function () {
    $bailleur = bailleurDeTest();
    $annonce = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)->post(route('bailleur.annonces.cloturer', $annonce))->assertRedirect();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Cloturee);
});

test('seul un brouillon peut être supprimé', function () {
    $bailleur = bailleurDeTest();
    $brouillon = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $publiee = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)->delete(route('bailleur.annonces.destroy', $brouillon))->assertRedirect();
    $this->actingAs($bailleur)->delete(route('bailleur.annonces.destroy', $publiee))->assertForbidden();

    $this->assertSoftDeleted($brouillon);
    expect(Annonce::find($publiee->id))->not->toBeNull();
});
