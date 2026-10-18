<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\StatutVerification;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Commande;
use App\Models\Forfait;
use App\Models\Moderation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function modoUtilisateur(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function modoBailleurVerifie(): User
{
    $user = modoUtilisateur(Role::Bailleur);

    BailleurProfil::create([
        'user_id' => $user->id,
        'piece_identite_path' => 'pieces-identite/x.jpg',
        'statut_verification' => StatutVerification::Verifie,
    ]);

    return $user;
}

function modoAnnonce(User $bailleur, bool $payee = true, int $duree = 30): Annonce
{
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);
    $annonce->transitionner(StatutAnnonce::EnModeration);

    if ($payee) {
        $forfait = Forfait::create([
            'nom' => 'Forfait test',
            'type_bien' => null,
            'duree_jours' => $duree,
            'prix' => 2000,
            'actif' => true,
            'ordre' => 1,
        ]);

        $commande = Commande::create([
            'user_id' => $bailleur->id,
            'annonce_id' => $annonce->id,
            'forfait_id' => $forfait->id,
            'libelle' => $forfait->nom,
            'duree_jours' => $duree,
            'montant' => 2000,
        ]);
        $commande->forceFill(['statut' => StatutCommande::Payee, 'payee_le' => now()])->save();
    }

    return $annonce;
}

test('un client et un bailleur ne peuvent pas accéder à la modération', function () {
    $this->actingAs(modoUtilisateur(Role::Client))->get(route('admin.moderation.index'))->assertForbidden();
    $this->actingAs(modoUtilisateur(Role::Bailleur))->get(route('admin.moderation.index'))->assertForbidden();
});

test('un modérateur et un administrateur accèdent à la file de modération', function () {
    $this->actingAs(modoUtilisateur(Role::Moderateur))->get(route('admin.moderation.index'))->assertOk();
    $this->actingAs(modoUtilisateur(Role::Admin))->get(route('admin.moderation.index'))->assertOk();
});

test('la file ne contient que les annonces en modération', function () {
    $bailleur = modoBailleurVerifie();
    modoAnnonce($bailleur);
    Annonce::factory()->create(['user_id' => $bailleur->id]); // brouillon

    $this->actingAs(modoUtilisateur(Role::Moderateur))
        ->get(route('admin.moderation.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Moderation/Index')
            ->has('annonces.data', 1));
});

test('valider publie l\'annonce et consomme le paiement', function () {
    $bailleur = modoBailleurVerifie();
    $annonce = modoAnnonce($bailleur);
    $moderateur = modoUtilisateur(Role::Moderateur);

    $this->actingAs($moderateur)->post(route('admin.moderation.valider', $annonce))->assertRedirect();

    $annonce->refresh();

    expect($annonce->statut)->toBe(StatutAnnonce::Publiee)
        ->and($annonce->publiee_le)->not->toBeNull()
        ->and($annonce->expire_le->isBetween(now()->addDays(29), now()->addDays(31)))->toBeTrue()
        ->and(Annonce::publiees()->count())->toBe(1)
        ->and(Commande::first()->consommee_le)->not->toBeNull()
        ->and(Moderation::first()->decision)->toBe('valide')
        ->and(Moderation::first()->moderateur_id)->toBe($moderateur->id);
});

test('la durée de publication suit le forfait payé', function () {
    $annonce = modoAnnonce(modoBailleurVerifie(), true, 60);

    $this->actingAs(modoUtilisateur(Role::Admin))->post(route('admin.moderation.valider', $annonce));

    expect($annonce->fresh()->expire_le->isBetween(now()->addDays(59), now()->addDays(61)))->toBeTrue();
});

test('on ne peut pas publier sans paiement disponible', function () {
    $annonce = modoAnnonce(modoBailleurVerifie(), false);

    $this->actingAs(modoUtilisateur(Role::Admin))
        ->post(route('admin.moderation.valider', $annonce))
        ->assertSessionHasErrors('moderation');

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration)
        ->and(Annonce::publiees()->count())->toBe(0);
});

test('on ne peut pas valider une annonce qui n\'est pas en modération', function () {
    $annonce = Annonce::factory()->create(); // brouillon

    $this->actingAs(modoUtilisateur(Role::Admin))
        ->post(route('admin.moderation.valider', $annonce))
        ->assertSessionHasErrors('moderation');

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('une annonce ne peut pas être validée deux fois', function () {
    $annonce = modoAnnonce(modoBailleurVerifie());
    $admin = modoUtilisateur(Role::Admin);

    $this->actingAs($admin)->post(route('admin.moderation.valider', $annonce))->assertRedirect();
    $this->actingAs($admin)->post(route('admin.moderation.valider', $annonce))->assertSessionHasErrors('moderation');

    expect(Moderation::count())->toBe(1);
});

test('le rejet exige un motif', function () {
    $annonce = modoAnnonce(modoBailleurVerifie());

    $this->actingAs(modoUtilisateur(Role::Admin))
        ->post(route('admin.moderation.rejeter', $annonce), [])
        ->assertSessionHasErrors('motif_rejet');

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});

test('rejeter renvoie l\'annonce au bailleur avec le motif', function () {
    $annonce = modoAnnonce(modoBailleurVerifie());

    $this->actingAs(modoUtilisateur(Role::Moderateur))
        ->post(route('admin.moderation.rejeter', $annonce), ['motif_rejet' => 'Photos manquantes'])
        ->assertRedirect();

    $annonce->refresh();

    expect($annonce->statut)->toBe(StatutAnnonce::Rejetee)
        ->and($annonce->motif_rejet)->toBe('Photos manquantes')
        ->and(Moderation::first()->decision)->toBe('rejete')
        ->and(Moderation::first()->motif)->toBe('Photos manquantes');
});

test('après un rejet, le bailleur corrige et resoumet sans repayer', function () {
    $bailleur = modoBailleurVerifie();
    $annonce = modoAnnonce($bailleur);

    $this->actingAs(modoUtilisateur(Role::Moderateur))
        ->post(route('admin.moderation.rejeter', $annonce), ['motif_rejet' => 'Description trop courte']);

    $this->actingAs($bailleur)->post(route('bailleur.annonces.rouvrir', $annonce))->assertRedirect();
    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);

    $this->actingAs($bailleur)->post(route('bailleur.annonces.soumettre', $annonce))->assertRedirect();
    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});

test('sans paiement disponible, la soumission demande un paiement', function () {
    $bailleur = modoBailleurVerifie();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)->post(route('bailleur.annonces.soumettre', $annonce))->assertRedirect();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement);
});

test('un bailleur ne peut pas valider sa propre annonce', function () {
    $bailleur = modoBailleurVerifie();
    $annonce = modoAnnonce($bailleur);

    $this->actingAs($bailleur)->post(route('admin.moderation.valider', $annonce))->assertForbidden();

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnModeration);
});
