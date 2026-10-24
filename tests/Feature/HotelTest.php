<?php

use App\Enums\Periodicite;
use App\Enums\Role;
use App\Enums\StatutVerification;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Forfait;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\ForfaitSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function htlBailleur(): User
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

function htlQuartier(): Quartier
{
    $ville = Ville::firstOrCreate(['nom' => "N'Djaména"]);

    return Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => 'Moursal']);
}

test('un hôtel publie une chambre facturée à la nuit', function () {
    $this->actingAs(htlBailleur())->post(route('bailleur.annonces.store'), [
        'quartier_id' => htlQuartier()->id,
        'titre' => 'Chambre climatisée au centre de l\'hôtel',
        'description' => str_repeat('Chambre propre avec petit déjeuner. ', 2),
        'type_bien' => 'hotel',
        'type_offre' => 'location',
        'prix' => 25000,
        'periodicite' => 'nuit',
    ])->assertSessionHasNoErrors();

    $annonce = Annonce::first();

    expect($annonce->type_bien)->toBe(TypeBien::Hotel)
        ->and($annonce->type_bien->libelle())->toBe('Hôtel / nuitée')
        ->and($annonce->periodicite)->toBe(Periodicite::Nuit)
        ->and($annonce->periodicite->libelle())->toBe('par nuit');
});

test('la recherche propose le type Hôtel et le filtre correctement', function () {
    Annonce::factory()->publiee()->create(['type_bien' => TypeBien::Hotel, 'periodicite' => Periodicite::Nuit]);
    Annonce::factory()->publiee()->create(['type_bien' => TypeBien::Maison]);

    $this->get(route('annonces.index', ['type_bien' => 'hotel']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('types_bien', 5)
            ->where('types_bien.4.value', 'hotel')
            ->has('annonces.data', 1)
            ->where('annonces.data.0.periodicite', 'par nuit')
            ->where('annonces.data.0.type_code', 'hotel'));
});

test('les forfaits des hôtels sont créés une seule fois', function () {
    $this->seed(ForfaitSeeder::class);
    $this->seed(ForfaitSeeder::class);

    expect(Forfait::where('type_bien', 'hotel')->count())->toBe(2)
        ->and(Forfait::pour(TypeBien::Hotel)->actifs()->count())->toBe(2);
});

test('la page des tarifs signale un type de bien sans forfait, hôtels compris', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Forfait::create([
        'nom' => 'Chambre · 30 jours',
        'type_bien' => TypeBien::Chambre,
        'duree_jours' => 30,
        'prix' => 2000,
        'actif' => true,
        'ordre' => 30,
    ]);

    $this->actingAs($admin)->get(route('admin.forfaits.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('couverture.4.type', 'Hôtel / nuitée')
            ->where('couverture.4.actifs', 0));
});

test('le nom de l\'agence ou de l\'hôtel est obligatoire pour ce type de compte', function () {
    $this->actingAs(htlBailleur())
        ->post(route('bailleur.profil.update'), ['type' => 'agence'])
        ->assertSessionHasErrors(['nom_commercial' => "Le nom de l'agence ou de l'hôtel est obligatoire."]);
});
