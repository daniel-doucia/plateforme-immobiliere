<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\StatutVerification;
use App\Enums\TypeBien;
use App\Models\AlerteRecherche;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Commande;
use App\Models\Favori;
use App\Models\Forfait;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function tbUtilisateur(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function tbBailleurVerifie(): User
{
    $user = tbUtilisateur(Role::Bailleur);

    BailleurProfil::create([
        'user_id' => $user->id,
        'piece_identite_path' => 'pieces-identite/x.jpg',
        'statut_verification' => StatutVerification::Verifie,
    ]);

    return $user;
}

function tbCommandePayee(Annonce $annonce, int $montant, $quand = null): Commande
{
    $forfait = Forfait::firstOrCreate(
        ['nom' => 'Forfait tb'],
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

    $commande->forceFill(['statut' => StatutCommande::Payee, 'payee_le' => $quand ?? now()])->save();

    return $commande;
}

test('le tableau de bord admin expose l\'évolution sur six mois', function () {
    Annonce::factory()->count(3)->create();
    Annonce::factory()->create(['created_at' => now()->startOfMonth()->subMonths(2)->addDays(5)]);

    $this->actingAs(tbUtilisateur(Role::Admin))->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('evolution', 6)
            ->where('evolution.5.valeur', 3)
            ->where('evolution.3.valeur', 1)
            ->where('evolution.0.valeur', 0));
});

test('la répartition compte les annonces par type de bien, sans les annonces supprimées', function () {
    Annonce::factory()->count(2)->create(['type_bien' => TypeBien::Chambre]);
    Annonce::factory()->create(['type_bien' => TypeBien::Terrain]);
    Annonce::factory()->create(['type_bien' => TypeBien::Terrain])->delete();

    $this->actingAs(tbUtilisateur(Role::Admin))->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('repartition', 4)
            ->where('repartition.0.type', 'chambre')
            ->where('repartition.0.total', 2)
            ->where('repartition.1.total', 0)
            ->where('repartition.3.type', 'terrain')
            ->where('repartition.3.total', 1));
});

test('les comptes bailleurs et clients sont comptés avec les nouveaux du mois', function () {
    tbUtilisateur(Role::Bailleur);
    tbUtilisateur(Role::Bailleur);
    tbUtilisateur(Role::Client);
    $ancien = tbUtilisateur(Role::Client);
    $ancien->forceFill(['created_at' => now()->startOfMonth()->subMonth()])->save();

    $this->actingAs(tbUtilisateur(Role::Admin))->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('comptes.bailleurs', 2)
            ->where('comptes.bailleurs_nouveaux', 2)
            ->where('comptes.clients', 2)
            ->where('comptes.clients_nouveaux', 1));
});

test('les revenus distinguent le total et le mois en cours', function () {
    $bailleur = tbUtilisateur(Role::Bailleur);
    tbCommandePayee(Annonce::factory()->create(['user_id' => $bailleur->id]), 5000);
    tbCommandePayee(Annonce::factory()->create(['user_id' => $bailleur->id]), 3000, now()->startOfMonth()->subDays(3));

    $this->actingAs(tbUtilisateur(Role::Admin))->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('revenus.total', 8000)
            ->where('revenus.mois', 5000));
});

test('les annonces en modération et les derniers paiements sont listés', function () {
    $bailleur = tbUtilisateur(Role::Bailleur);

    $enModeration = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $enModeration->transitionner(StatutAnnonce::EnAttentePaiement);
    $enModeration->transitionner(StatutAnnonce::EnModeration);
    Annonce::factory()->create(['user_id' => $bailleur->id]); // brouillon : absent de la liste

    tbCommandePayee($enModeration, 2000);

    $this->actingAs(tbUtilisateur(Role::Admin))->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('en_attente', 1)
            ->where('en_attente.0.titre', $enModeration->titre)
            ->where('en_attente.0.bailleur', $bailleur->name)
            ->has('derniers_paiements', 1)
            ->where('derniers_paiements.0.montant', 2000)
            ->where('derniers_paiements.0.bailleur', $bailleur->name));
});

test('un bailleur voit ses vues, ses favoris reçus et peut créer une annonce', function () {
    $bailleur = tbBailleurVerifie();
    $premiere = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id, 'vues_count' => 7]);
    Annonce::factory()->create(['user_id' => $bailleur->id, 'vues_count' => 3]);
    Annonce::factory()->publiee()->create(['vues_count' => 50]); // celle d'un autre bailleur

    Favori::create(['user_id' => tbUtilisateur(Role::Client)->id, 'annonce_id' => $premiere->id]);

    $this->actingAs($bailleur)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vues', 10)
            ->where('favoris_recus', 1)
            ->where('peut_creer', true)
            ->has('annonces_recentes', 2));
});

test('un bailleur non vérifié ne peut pas créer d\'annonce depuis son tableau de bord', function () {
    $this->actingAs(tbUtilisateur(Role::Bailleur))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('peut_creer', false));
});

test('un client reçoit des recommandations proches de ses favoris, hors favoris', function () {
    $client = tbUtilisateur(Role::Client);
    $favorite = Annonce::factory()->publiee()->create(['type_bien' => TypeBien::Chambre]);
    $proche = Annonce::factory()->publiee()->create(['type_bien' => TypeBien::Chambre]);
    Annonce::factory()->publiee()->create(['type_bien' => TypeBien::Maison]);

    Favori::create(['user_id' => $client->id, 'annonce_id' => $favorite->id]);

    $this->actingAs($client)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recommandees', 1)
            ->where('recommandees.0.titre', $proche->titre));
});

test('sans favori, les recommandations sont les trois annonces les plus récentes', function () {
    Annonce::factory()->publiee()->count(4)->create();

    $this->actingAs(tbUtilisateur(Role::Client))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('recommandees', 3));
});

test('un bailleur n\'a pas de recommandations', function () {
    Annonce::factory()->publiee()->count(2)->create();

    $this->actingAs(tbBailleurVerifie())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('recommandees', 0));
});

test('les compteurs de favoris et d\'alertes sont ceux de l\'utilisateur connecté', function () {
    $client = tbUtilisateur(Role::Client);
    $autre = tbUtilisateur(Role::Client);

    Favori::create(['user_id' => $client->id, 'annonce_id' => Annonce::factory()->publiee()->create()->id]);
    Favori::create(['user_id' => $client->id, 'annonce_id' => Annonce::factory()->publiee()->create()->id]);
    Favori::create(['user_id' => $autre->id, 'annonce_id' => Annonce::factory()->publiee()->create()->id]);

    AlerteRecherche::create(['user_id' => $client->id, 'type_bien' => 'chambre', 'actif' => true]);
    AlerteRecherche::create(['user_id' => $client->id, 'type_bien' => 'terrain', 'actif' => false]);
    AlerteRecherche::create(['user_id' => $autre->id, 'type_bien' => 'maison', 'actif' => true]);

    $this->actingAs($client)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('favoris_count', 2)
            ->where('alertes_count', 1));
});
