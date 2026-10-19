<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Forfait;
use App\Models\User;
use App\Notifications\AnnonceBientotExpiree;
use App\Services\Moderation\ModererAnnonce;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function expBailleur(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Bailleur->value);

    return $user;
}

test('la commande expire les annonces dont la durée est dépassée', function () {
    $expiree = Annonce::factory()->expiree()->create();
    $valide = Annonce::factory()->publiee()->create();
    $brouillon = Annonce::factory()->create();

    $this->artisan('annonces:expirer')->assertSuccessful();

    expect($expiree->fresh()->statut)->toBe(StatutAnnonce::Expiree)
        ->and($valide->fresh()->statut)->toBe(StatutAnnonce::Publiee)
        ->and($brouillon->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('relancer la commande ne change rien de plus', function () {
    $annonce = Annonce::factory()->expiree()->create();

    $this->artisan('annonces:expirer')->assertSuccessful();
    $this->artisan('annonces:expirer')->assertSuccessful();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Expiree);
});

test('un rappel n\'est envoyé qu\'une seule fois avant l\'expiration', function () {
    Notification::fake();
    $annonce = Annonce::factory()->publiee()->create(['expire_le' => now()->addDays(2)]);

    $this->artisan('annonces:rappeler-expiration')->assertSuccessful();
    $this->artisan('annonces:rappeler-expiration')->assertSuccessful();

    Notification::assertSentToTimes($annonce->user, AnnonceBientotExpiree::class, 1);
    expect($annonce->fresh()->rappel_expiration_le)->not->toBeNull();
});

test('pas de rappel pour une annonce lointaine, expirée ou non publiée', function () {
    Notification::fake();
    Annonce::factory()->publiee()->create(['expire_le' => now()->addDays(20)]);
    Annonce::factory()->expiree()->create();
    Annonce::factory()->create();

    $this->artisan('annonces:rappeler-expiration')->assertSuccessful();

    Notification::assertNothingSent();
});

test('le bailleur renouvelle une annonce expirée et choisit un forfait', function () {
    $bailleur = expBailleur();
    $annonce = Annonce::factory()->expiree()->create(['user_id' => $bailleur->id]);
    $this->artisan('annonces:expirer')->assertSuccessful();

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.renouveler', $annonce))
        ->assertRedirect(route('bailleur.commandes.create', $annonce));

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement);
});

test('on ne peut pas renouveler l\'annonce d\'un autre', function () {
    $annonce = Annonce::factory()->expiree()->create();
    $this->artisan('annonces:expirer')->assertSuccessful();

    $this->actingAs(expBailleur())
        ->post(route('bailleur.annonces.renouveler', $annonce))
        ->assertForbidden();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Expiree);
});

test('on ne peut pas renouveler une annonce encore en ligne', function () {
    $bailleur = expBailleur();
    $annonce = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.renouveler', $annonce))
        ->assertForbidden();
});

test('la validation efface le rappel pour que le cycle suivant soit rappelé', function () {
    $bailleur = expBailleur();
    $annonce = Annonce::factory()->create([
        'user_id' => $bailleur->id,
        'rappel_expiration_le' => now()->subDay(),
    ]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);
    $annonce->transitionner(StatutAnnonce::EnModeration);

    $forfait = Forfait::create([
        'nom' => 'Forfait test',
        'type_bien' => null,
        'duree_jours' => 30,
        'prix' => 2000,
        'actif' => true,
        'ordre' => 1,
    ]);
    $commande = Commande::create([
        'user_id' => $bailleur->id,
        'annonce_id' => $annonce->id,
        'forfait_id' => $forfait->id,
        'libelle' => $forfait->nom,
        'duree_jours' => 30,
        'montant' => 2000,
    ]);
    $commande->forceFill(['statut' => StatutCommande::Payee, 'payee_le' => now()])->save();

    app(ModererAnnonce::class)->valider($annonce, User::factory()->create());

    expect($annonce->fresh()->rappel_expiration_le)->toBeNull();
});
