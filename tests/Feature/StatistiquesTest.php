<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Forfait;
use App\Models\Moderation;
use App\Models\User;
use App\Services\Statistiques\StatistiquesAnnonces;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function statUtilisateur(Role $role, array $attributs = []): User
{
    $user = User::factory()->create($attributs);
    $user->assignRole($role->value);

    return $user;
}

function statCommande(Annonce $annonce, int $montant, StatutCommande $statut = StatutCommande::Payee): Commande
{
    $forfait = Forfait::firstOrCreate(
        ['nom' => 'Forfait stat'],
        ['type_bien' => null, 'duree_jours' => 30, 'prix' => 2000, 'actif' => true, 'ordre' => 1],
    );

    $commande = Commande::create([
        'user_id' => $annonce->user_id,
        'annonce_id' => $annonce->id,
        'forfait_id' => $forfait->id,
        'libelle' => $forfait->nom,
        'duree_jours' => 30,
        'montant' => $montant,
    ]);

    $commande->forceFill([
        'statut' => $statut,
        'payee_le' => $statut === StatutCommande::Payee ? now() : null,
    ])->save();

    return $commande;
}

/** Crée 4 annonces : 1 brouillon, 1 en modération, 1 rejetée, 1 publiée. */
function statJeuDeDonnees(User $bailleur): array
{
    $brouillon = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $enModeration = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $enModeration->transitionner(StatutAnnonce::EnAttentePaiement);
    $enModeration->transitionner(StatutAnnonce::EnModeration);

    $rejetee = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $rejetee->forceFill(['statut' => StatutAnnonce::Rejetee])->save();

    $publiee = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);

    statCommande($enModeration, 2000);
    statCommande($publiee, 5000);
    statCommande($publiee, 3000);                          // renouvellement de la même annonce
    statCommande($rejetee, 9999, StatutCommande::Annulee); // jamais comptée

    // Historique : la rejetée et la publiée (rejetée puis corrigée) ont chacune été rejetées une fois
    $moderateur = User::factory()->create();
    foreach ([$rejetee, $publiee] as $annonce) {
        Moderation::create([
            'annonce_id' => $annonce->id,
            'moderateur_id' => $moderateur->id,
            'decision' => 'rejete',
            'motif' => 'Photos floues',
        ]);
    }

    return compact('brouillon', 'enModeration', 'rejetee', 'publiee');
}

test('les statistiques comptent chaque statut, les annonces payées, le montant et les rejets', function () {
    $bailleur = statUtilisateur(Role::Bailleur);
    statJeuDeDonnees($bailleur);

    $stats = app(StatistiquesAnnonces::class)->parBailleur([$bailleur->id])[$bailleur->id];

    expect($stats['total'])->toBe(4)
        ->and($stats['brouillon'])->toBe(1)
        ->and($stats['en_moderation'])->toBe(1)
        ->and($stats['rejetee'])->toBe(1)
        ->and($stats['publiee'])->toBe(1)
        ->and($stats['expiree'])->toBe(0)
        ->and($stats['annonces_payees'])->toBe(2)   // 2 annonces, malgré 3 commandes payées
        ->and($stats['montant_paye'])->toBe(10000)  // les 3 paiements, la commande annulée exclue
        ->and($stats['rejets_historique'])->toBe(2);
});

test('les compteurs d\'un bailleur ne mélangent pas ceux des autres', function () {
    $a = statUtilisateur(Role::Bailleur);
    $b = statUtilisateur(Role::Bailleur);
    statJeuDeDonnees($a);
    Annonce::factory()->create(['user_id' => $b->id]);

    $stats = app(StatistiquesAnnonces::class)->parBailleur([$a->id, $b->id]);

    expect($stats[$a->id]['total'])->toBe(4)
        ->and($stats[$b->id]['total'])->toBe(1)
        ->and($stats[$b->id]['annonces_payees'])->toBe(0)
        ->and($stats[$b->id]['montant_paye'])->toBe(0);
});

test('un bailleur sans annonce a tous ses compteurs à zéro', function () {
    $bailleur = statUtilisateur(Role::Bailleur);

    $stats = app(StatistiquesAnnonces::class)->parBailleur([$bailleur->id])[$bailleur->id];

    expect(array_sum($stats))->toBe(0);
});

test('les annonces supprimées ne sont pas comptées', function () {
    $bailleur = statUtilisateur(Role::Bailleur);
    $jeu = statJeuDeDonnees($bailleur);

    $jeu['brouillon']->delete();

    $stats = app(StatistiquesAnnonces::class)->parBailleur([$bailleur->id])[$bailleur->id];

    expect($stats['total'])->toBe(3)->and($stats['brouillon'])->toBe(0);
});

test('les statistiques globales additionnent tous les bailleurs', function () {
    statJeuDeDonnees(statUtilisateur(Role::Bailleur));
    statJeuDeDonnees(statUtilisateur(Role::Bailleur));

    $globales = app(StatistiquesAnnonces::class)->globales();

    expect($globales['total'])->toBe(8)
        ->and($globales['annonces_payees'])->toBe(4)
        ->and($globales['montant_paye'])->toBe(20000)
        ->and($globales['rejets_historique'])->toBe(4);
});

test('la page des bailleurs est réservée à l\'administrateur', function () {
    foreach ([Role::Client, Role::Bailleur, Role::Moderateur] as $role) {
        $this->actingAs(statUtilisateur($role))->get(route('admin.bailleurs.index'))->assertForbidden();
    }

    auth()->logout();
    $this->get(route('admin.bailleurs.index'))->assertRedirect('/login');
});

test('l\'administrateur voit les compteurs de chaque bailleur', function () {
    $bailleur = statUtilisateur(Role::Bailleur);
    statJeuDeDonnees($bailleur);

    $this->actingAs(statUtilisateur(Role::Admin))
        ->get(route('admin.bailleurs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Bailleurs/Index')
            ->has('bailleurs.data', 1)
            ->where('bailleurs.data.0.stats.total', 4)
            ->where('bailleurs.data.0.stats.annonces_payees', 2)
            ->where('bailleurs.data.0.stats.montant_paye', 10000));
});

test('la recherche filtre les bailleurs par nom', function () {
    statUtilisateur(Role::Bailleur, ['name' => 'Abdoulaye Test']);
    statUtilisateur(Role::Bailleur, ['name' => 'Moussa Test']);

    $this->actingAs(statUtilisateur(Role::Admin))
        ->get(route('admin.bailleurs.index', ['q' => 'abdou']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('bailleurs.data', 1)
            ->where('bailleurs.data.0.nom', 'Abdoulaye Test'));
});

test('un bailleur ne voit que ses propres compteurs sur son tableau de bord', function () {
    $moi = statUtilisateur(Role::Bailleur);
    statJeuDeDonnees($moi);
    Annonce::factory()->count(3)->create(['user_id' => statUtilisateur(Role::Bailleur)->id]);

    $this->actingAs($moi)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 4)
            ->where('stats.annonces_payees', 2));
});

test('un client n\'a pas de compteurs d\'annonces', function () {
    $this->actingAs(statUtilisateur(Role::Client))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('stats', null));
});

test('le tableau de bord admin expose les totaux de la plateforme', function () {
    statJeuDeDonnees(statUtilisateur(Role::Bailleur));

    $this->actingAs(statUtilisateur(Role::Admin))->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('annonces.total', 4)
            ->where('annonces.montant_paye', 10000));
});
