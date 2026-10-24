<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Forfait;
use App\Models\ForfaitModification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function tarifUtilisateur(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function tarifForfait(array $surcharge = []): Forfait
{
    return Forfait::create(array_merge([
        'nom' => 'Chambre · 30 jours',
        'type_bien' => TypeBien::Chambre,
        'duree_jours' => 30,
        'prix' => 2000,
        'actif' => true,
        'ordre' => 30,
    ], $surcharge));
}

function tarifDonnees(array $surcharge = []): array
{
    return array_merge([
        'nom' => 'Terrain · 90 jours',
        'type_bien' => 'terrain',
        'duree_jours' => 90,
        'prix' => 25000,
        'actif' => true,
        'ordre' => 90,
    ], $surcharge);
}

function tarifDonneesDe(Forfait $forfait, array $surcharge = []): array
{
    return array_merge([
        'nom' => $forfait->nom,
        'type_bien' => $forfait->type_bien?->value,
        'duree_jours' => $forfait->duree_jours,
        'prix' => $forfait->prix,
        'actif' => $forfait->actif,
        'ordre' => $forfait->ordre,
    ], $surcharge);
}

function tarifAnnonceEnAttente(User $bailleur): Annonce
{
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id, 'type_bien' => TypeBien::Chambre]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

    return $annonce;
}

test('seul l\'administrateur accède aux tarifs', function () {
    foreach ([Role::Client, Role::Bailleur, Role::Moderateur] as $role) {
        $this->actingAs(tarifUtilisateur($role))->get(route('admin.forfaits.index'))->assertForbidden();
        $this->actingAs(tarifUtilisateur($role))->post(route('admin.forfaits.store'), tarifDonnees())->assertForbidden();
    }

    expect(Forfait::count())->toBe(0);
});

test('un visiteur non connecté est redirigé vers la connexion', function () {
    $this->get(route('admin.forfaits.index'))->assertRedirect('/login');
});

test('l\'administrateur voit la liste des forfaits', function () {
    tarifForfait();

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->get(route('admin.forfaits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Forfaits/Index')
            ->has('forfaits', 1)
            ->has('types', 5));
});

test('l\'administrateur crée un forfait et la création est journalisée', function () {
    $admin = tarifUtilisateur(Role::Admin);

    $this->actingAs($admin)
        ->post(route('admin.forfaits.store'), tarifDonnees())
        ->assertSessionHasNoErrors();

    $forfait = Forfait::where('nom', 'Terrain · 90 jours')->first();
    $trace = ForfaitModification::first();

    expect($forfait)->not->toBeNull()
        ->and($forfait->prix)->toBe(25000)
        ->and($forfait->type_bien)->toBe(TypeBien::Terrain)
        ->and(ForfaitModification::count())->toBe(1)
        ->and($trace->avant)->toBeNull()
        ->and($trace->apres['prix'])->toBe(25000)
        ->and($trace->user_id)->toBe($admin->id);
});

test('un doublon type de bien et durée est refusé', function () {
    tarifForfait(['type_bien' => TypeBien::Terrain, 'duree_jours' => 90]);

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->post(route('admin.forfaits.store'), tarifDonnees())
        ->assertSessionHasErrors('duree_jours');

    expect(Forfait::count())->toBe(1);
});

test('un doublon est aussi refusé pour les forfaits valables pour tous les biens', function () {
    tarifForfait(['type_bien' => null, 'duree_jours' => 45]);

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->post(route('admin.forfaits.store'), tarifDonnees(['type_bien' => null, 'duree_jours' => 45]))
        ->assertSessionHasErrors('duree_jours');

    expect(Forfait::count())->toBe(1);
});

test('un prix invalide est refusé', function () {
    $admin = tarifUtilisateur(Role::Admin);

    $this->actingAs($admin)->post(route('admin.forfaits.store'), tarifDonnees(['prix' => 50]))
        ->assertSessionHasErrors('prix');
    $this->actingAs($admin)->post(route('admin.forfaits.store'), tarifDonnees(['prix' => 'gratuit']))
        ->assertSessionHasErrors('prix');

    expect(Forfait::count())->toBe(0);
});

test('une durée invalide est refusée', function () {
    $admin = tarifUtilisateur(Role::Admin);

    $this->actingAs($admin)->post(route('admin.forfaits.store'), tarifDonnees(['duree_jours' => 0]))
        ->assertSessionHasErrors('duree_jours');
    $this->actingAs($admin)->post(route('admin.forfaits.store'), tarifDonnees(['duree_jours' => 400]))
        ->assertSessionHasErrors('duree_jours');

    expect(Forfait::count())->toBe(0);
});

test('un type de bien inconnu est refusé', function () {
    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->post(route('admin.forfaits.store'), tarifDonnees(['type_bien' => 'chateau']))
        ->assertSessionHasErrors('type_bien');

    expect(Forfait::count())->toBe(0);
});

test('modifier le prix est enregistré et journalisé avec l\'avant et l\'après', function () {
    $admin = tarifUtilisateur(Role::Admin);
    $forfait = tarifForfait();

    $this->actingAs($admin)
        ->patch(route('admin.forfaits.update', $forfait), tarifDonneesDe($forfait, ['prix' => 3500]))
        ->assertSessionHasNoErrors();

    $trace = ForfaitModification::first();

    expect($forfait->fresh()->prix)->toBe(3500)
        ->and(ForfaitModification::count())->toBe(1)
        ->and($trace->avant['prix'])->toBe(2000)
        ->and($trace->apres['prix'])->toBe(3500)
        ->and($trace->user_id)->toBe($admin->id);
});

test('un enregistrement sans changement ne crée aucune trace', function () {
    $forfait = tarifForfait();

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->patch(route('admin.forfaits.update', $forfait), tarifDonneesDe($forfait))
        ->assertSessionHasNoErrors();

    expect(ForfaitModification::count())->toBe(0);
});

test('un changement de prix ne modifie pas les commandes déjà passées', function () {
    $forfait = tarifForfait();
    $bailleur = tarifUtilisateur(Role::Bailleur);
    $annonce = tarifAnnonceEnAttente($bailleur);

    $this->actingAs($bailleur)
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id])
        ->assertRedirect();

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->patch(route('admin.forfaits.update', $forfait), tarifDonneesDe($forfait, ['prix' => 9000]))
        ->assertSessionHasNoErrors();

    expect(Commande::first()->montant)->toBe(2000)
        ->and($forfait->fresh()->prix)->toBe(9000);
});

test('une nouvelle commande utilise le nouveau prix', function () {
    $forfait = tarifForfait();

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->patch(route('admin.forfaits.update', $forfait), tarifDonneesDe($forfait, ['prix' => 3500]));

    $bailleur = tarifUtilisateur(Role::Bailleur);
    $annonce = tarifAnnonceEnAttente($bailleur);

    $this->actingAs($bailleur)
        ->post(route('bailleur.commandes.store', $annonce), ['forfait_id' => $forfait->id])
        ->assertRedirect();

    expect(Commande::first()->montant)->toBe(3500);
});

test('un forfait désactivé n\'est plus proposé aux bailleurs', function () {
    $forfait = tarifForfait();

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->patch(route('admin.forfaits.update', $forfait), tarifDonneesDe($forfait, ['actif' => false]))
        ->assertSessionHasNoErrors();

    $bailleur = tarifUtilisateur(Role::Bailleur);
    $annonce = tarifAnnonceEnAttente($bailleur);

    $this->actingAs($bailleur)
        ->get(route('bailleur.commandes.create', $annonce))
        ->assertInertia(fn (Assert $page) => $page->has('forfaits', 0));
});

test('un forfait jamais commandé peut être supprimé et la suppression est tracée', function () {
    $forfait = tarifForfait();

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->delete(route('admin.forfaits.destroy', $forfait))
        ->assertSessionHasNoErrors();

    expect(Forfait::find($forfait->id))->toBeNull()
        ->and(ForfaitModification::count())->toBe(1)
        ->and(ForfaitModification::first()->forfait_nom)->toBe('Chambre · 30 jours')
        ->and(ForfaitModification::first()->apres)->toBeNull();
});

test('un forfait déjà commandé ne peut pas être supprimé', function () {
    $forfait = tarifForfait();
    $annonce = tarifAnnonceEnAttente(tarifUtilisateur(Role::Bailleur));

    Commande::create([
        'user_id' => $annonce->user_id,
        'annonce_id' => $annonce->id,
        'forfait_id' => $forfait->id,
        'libelle' => $forfait->nom,
        'duree_jours' => 30,
        'montant' => 2000,
    ]);

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->delete(route('admin.forfaits.destroy', $forfait))
        ->assertSessionHasErrors('forfait');

    expect(Forfait::find($forfait->id))->not->toBeNull();
});

test('la page signale les types de bien sans forfait actif', function () {
    tarifForfait(); // uniquement pour les chambres

    $this->actingAs(tarifUtilisateur(Role::Admin))
        ->get(route('admin.forfaits.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('couverture', 5)
            ->where('couverture.0.actifs', 1)
            ->where('couverture.3.actifs', 0));
});
