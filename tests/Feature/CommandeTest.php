<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Forfait;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function bailleurPourCommande(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Bailleur->value);

    return $user;
}

function annonceEnAttente(User $user, TypeBien $type = TypeBien::Chambre): Annonce
{
    $annonce = Annonce::factory()->create(['user_id' => $user->id, 'type_bien' => $type]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

    return $annonce;
}

function forfaitDeTest(TypeBien $type = TypeBien::Chambre, int $prix = 2000, bool $actif = true): Forfait
{
    return Forfait::create([
        'nom' => 'Forfait test',
        'type_bien' => $type,
        'duree_jours' => 30,
        'prix' => $prix,
        'actif' => $actif,
        'ordre' => 1,
    ]);
}

test('la page de choix du forfait s\'affiche avec les forfaits adaptés', function () {
    $bailleur = bailleurPourCommande();
    $annonce = annonceEnAttente($bailleur);
    forfaitDeTest(TypeBien::Chambre);
    forfaitDeTest(TypeBien::Terrain);

    $this->actingAs($bailleur)->get(route('bailleur.commandes.create', $annonce))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bailleur/Commandes/Create')
            ->has('forfaits', 1));
});

test('le montant de la commande vient du forfait, pas du navigateur', function () {
    $bailleur = bailleurPourCommande();
    $annonce = annonceEnAttente($bailleur);
    $forfait = forfaitDeTest(TypeBien::Chambre, 2000);

    $this->actingAs($bailleur)
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id, 'montant' => 1])
        ->assertRedirect();

    $commande = Commande::first();

    expect($commande->montant)->toBe(2000)
        ->and($commande->statut)->toBe(StatutCommande::EnAttente)
        ->and($commande->user_id)->toBe($bailleur->id)
        ->and($commande->reference)->toStartWith('CMD-');
});

test('on ne peut pas commander pour un brouillon', function () {
    $bailleur = bailleurPourCommande();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $forfait = forfaitDeTest();

    $this->actingAs($bailleur)
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id])
        ->assertForbidden();

    expect(Commande::count())->toBe(0);
});

test('on ne peut pas commander pour l\'annonce d\'un autre', function () {
    $annonce = annonceEnAttente(bailleurPourCommande());
    $forfait = forfaitDeTest();

    $this->actingAs(bailleurPourCommande())
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id])
        ->assertForbidden();
});

test('un forfait d\'un autre type de bien est refusé', function () {
    $bailleur = bailleurPourCommande();
    $annonce = annonceEnAttente($bailleur, TypeBien::Chambre);
    $terrain = forfaitDeTest(TypeBien::Terrain);

    $this->actingAs($bailleur)
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $terrain->id])
        ->assertNotFound();
});

test('un forfait désactivé est refusé', function () {
    $bailleur = bailleurPourCommande();
    $annonce = annonceEnAttente($bailleur);
    $inactif = forfaitDeTest(TypeBien::Chambre, 2000, false);

    $this->actingAs($bailleur)
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $inactif->id])
        ->assertNotFound();
});

test('une nouvelle commande annule la précédente encore en attente', function () {
    $bailleur = bailleurPourCommande();
    $annonce = annonceEnAttente($bailleur);
    $forfait = forfaitDeTest();

    $this->actingAs($bailleur)->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id]);
    $this->actingAs($bailleur)->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id]);

    expect(Commande::where('statut', StatutCommande::EnAttente->value)->count())->toBe(1)
        ->and(Commande::where('statut', StatutCommande::Annulee->value)->count())->toBe(1);
});

test('seul le propriétaire voit sa commande', function () {
    $bailleur = bailleurPourCommande();
    $annonce = annonceEnAttente($bailleur);
    $forfait = forfaitDeTest();

    $this->actingAs($bailleur)->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id]);
    $commande = Commande::first();

    $this->actingAs($bailleur)->get(route('bailleur.commandes.show', $commande))->assertOk();
    $this->actingAs(bailleurPourCommande())->get(route('bailleur.commandes.show', $commande))->assertForbidden();
});
