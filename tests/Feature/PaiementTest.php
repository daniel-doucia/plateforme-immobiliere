<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Forfait;
use App\Models\Paiement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config([
        'paiement.faux.secret' => 'secret-de-test',
        'paiement.fournisseur_par_defaut' => 'faux',
    ]);
});

function bailleurPaiement(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Bailleur->value);

    return $user;
}

function commandeEnAttente(?User $user = null, int $prix = 2000): Commande
{
    $user ??= bailleurPaiement();

    $annonce = Annonce::factory()->create(['user_id' => $user->id, 'type_bien' => TypeBien::Chambre]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

    $forfait = Forfait::create([
        'nom' => 'Chambre · 30 jours',
        'type_bien' => TypeBien::Chambre,
        'duree_jours' => 30,
        'prix' => $prix,
        'actif' => true,
        'ordre' => 1,
    ]);

    return Commande::create([
        'user_id' => $user->id,
        'annonce_id' => $annonce->id,
        'forfait_id' => $forfait->id,
        'libelle' => $forfait->nom,
        'duree_jours' => 30,
        'montant' => $prix,
    ]);
}

function envoyerWebhook($test, Commande $commande, array $surcharge = [], ?string $signature = null)
{
    $corps = json_encode(array_merge([
        'event_id' => 'evt_'.uniqid(),
        'reference' => $commande->reference,
        'montant' => $commande->montant,
        'statut' => 'reussi',
    ], $surcharge));

    $signature ??= hash_hmac('sha256', $corps, 'secret-de-test');

    return $test->call('POST', '/webhooks/paiement/faux', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SIGNATURE' => $signature,
    ], $corps);
}

test('une signature invalide est refusée et ne change rien', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande, [], 'mauvaise-signature')->assertForbidden();

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente)
        ->and(Paiement::count())->toBe(0);
});

test('une signature absente est refusée', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande, [], '')->assertForbidden();

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente);
});

test('un paiement réussi valide la commande et envoie l\'annonce en modération', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande)->assertOk();

    expect($commande->fresh()->statut)->toBe(StatutCommande::Payee)
        ->and($commande->fresh()->payee_le)->not->toBeNull()
        ->and(Paiement::count())->toBe(1)
        ->and($commande->annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});

test('le même événement reçu deux fois ne paie qu\'une fois', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande, ['event_id' => 'evt_fixe'])->assertOk();
    envoyerWebhook($this, $commande, ['event_id' => 'evt_fixe'])->assertOk();

    expect(Paiement::count())->toBe(1)
        ->and($commande->annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});

test('un nouvel événement sur une commande déjà payée est ignoré', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande)->assertOk();
    envoyerWebhook($this, $commande)->assertOk();

    expect(Paiement::count())->toBe(1);
});

test('un montant différent est refusé', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande, ['montant' => 1])->assertStatus(422);

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente)
        ->and($commande->annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement)
        ->and(Paiement::count())->toBe(0);
});

test('un paiement échoué ne publie rien', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande, ['statut' => 'echec'])->assertOk();

    expect($commande->fresh()->statut)->toBe(StatutCommande::Echouee)
        ->and($commande->annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement)
        ->and(Paiement::first()->statut)->toBe('echoue');
});

test('une référence inconnue donne une erreur 404', function () {
    $commande = commandeEnAttente();

    envoyerWebhook($this, $commande, ['reference' => 'CMD-INCONNUE'])->assertNotFound();
});

test('un message signé mais mal formé est refusé', function () {
    $corps = 'ceci-nest-pas-du-json';

    $this->call('POST', '/webhooks/paiement/faux', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SIGNATURE' => hash_hmac('sha256', $corps, 'secret-de-test'),
    ], $corps)->assertStatus(422);
});

test('un paiement reçu après réouverture de l\'annonce la remet dans le circuit', function () {
    $commande = commandeEnAttente();
    $commande->annonce->transitionner(StatutAnnonce::Brouillon);

    envoyerWebhook($this, $commande)->assertOk();

    expect($commande->annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});

test('un fournisseur inconnu donne une erreur 404', function () {
    $this->call('POST', '/webhooks/paiement/inconnu', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], '{}')->assertNotFound();
});

test('le faux fournisseur est désactivé en production', function () {
    $commande = commandeEnAttente();
    $this->app['env'] = 'production';

    envoyerWebhook($this, $commande)->assertNotFound();

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente);
});

test('le propriétaire est envoyé vers la page de paiement', function () {
    $user = bailleurPaiement();
    $commande = commandeEnAttente($user);

    $this->actingAs($user)
        ->post(route('paiement.payer', $commande))
        ->assertRedirect(route('paiement.faux.show', $commande->reference));
});

test('on ne peut pas payer la commande d\'un autre', function () {
    $commande = commandeEnAttente();

    $this->actingAs(bailleurPaiement())
        ->post(route('paiement.payer', $commande))
        ->assertForbidden();
});

test('on ne peut pas payer une commande déjà payée', function () {
    $user = bailleurPaiement();
    $commande = commandeEnAttente($user);
    $commande->forceFill(['statut' => StatutCommande::Payee])->save();

    $this->actingAs($user)
        ->post(route('paiement.payer', $commande))
        ->assertForbidden();
});

test('la simulation d\'un paiement réussi passe par le webhook signé', function () {
    $user = bailleurPaiement();
    $commande = commandeEnAttente($user);

    $this->actingAs($user)
        ->post(route('paiement.faux.simuler', $commande->reference), ['resultat' => 'reussi'])
        ->assertRedirect(route('bailleur.commandes.show', $commande));

    expect($commande->fresh()->statut)->toBe(StatutCommande::Payee)
        ->and($commande->annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});

test('la simulation échoue proprement sans secret configuré', function () {
    config(['paiement.faux.secret' => '']);
    $user = bailleurPaiement();
    $commande = commandeEnAttente($user);

    $this->actingAs($user)
        ->post(route('paiement.faux.simuler', $commande->reference), ['resultat' => 'reussi'])
        ->assertSessionHasErrors('paiement');

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente);
});

test('un autre bailleur ne peut pas voir la page de paiement', function () {
    $commande = commandeEnAttente();

    $this->actingAs(bailleurPaiement())
        ->get(route('paiement.faux.show', $commande->reference))
        ->assertForbidden();
});

test('un résultat de simulation invalide est refusé', function () {
    $user = bailleurPaiement();
    $commande = commandeEnAttente($user);

    $this->actingAs($user)
        ->post(route('paiement.faux.simuler', $commande->reference), ['resultat' => 'gratuit'])
        ->assertSessionHasErrors('resultat');
});
