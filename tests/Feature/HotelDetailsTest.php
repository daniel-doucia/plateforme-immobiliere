<?php

use App\Enums\Role;
use App\Enums\StatutVerification;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\BailleurProfil;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function hdBailleur(): User
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

function hdDonnees(array $surcharge = []): array
{
    $ville = Ville::firstOrCreate(['nom' => "N'Djaména"]);
    $quartier = Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => 'Moursal']);

    return array_merge([
        'quartier_id' => $quartier->id,
        'titre' => 'Hôtel avec chambres climatisées au centre',
        'description' => str_repeat('Chambres propres, groupe électrogène en cas de coupure. ', 2),
        'type_bien' => 'hotel',
        'type_offre' => 'location',
        'prix' => 25000,
        'periodicite' => 'nuit',
        'details_hotel' => [
            'etoiles' => 3,
            'types_chambre' => ['ventilee', 'climatisee'],
            'equipements' => ['groupe_electrogene', 'wifi', 'restaurant'],
            'paiements' => ['especes', 'airtel_money'],
            'arrivee' => '14:00',
            'depart' => '12:00',
        ],
    ], $surcharge);
}

test('un hôtel enregistre ses détails propres', function () {
    $this->actingAs(hdBailleur())
        ->post(route('bailleur.annonces.store'), hdDonnees())
        ->assertSessionHasNoErrors();

    $annonce = Annonce::firstOrFail();

    expect($annonce->details_hotel['etoiles'])->toBe(3)
        ->and($annonce->details_hotel['equipements'])->toBe(['groupe_electrogene', 'wifi', 'restaurant'])
        ->and($annonce->details_hotel['arrivee'])->toBe('14:00');
});

test('un équipement hors de la liste officielle est refusé', function () {
    $donnees = hdDonnees();
    $donnees['details_hotel']['equipements'] = ['wifi', 'lance-missiles'];

    $this->actingAs(hdBailleur())
        ->post(route('bailleur.annonces.store'), $donnees)
        ->assertSessionHasErrors('details_hotel.equipements.1');

    expect(Annonce::count())->toBe(0);
});

test('un classement hors de 0 à 5 étoiles est refusé', function () {
    $donnees = hdDonnees();
    $donnees['details_hotel']['etoiles'] = 6;

    $this->actingAs(hdBailleur())
        ->post(route('bailleur.annonces.store'), $donnees)
        ->assertSessionHasErrors('details_hotel.etoiles');
});

test('une heure invalide est refusée', function () {
    $donnees = hdDonnees();
    $donnees['details_hotel']['arrivee'] = '25:99';

    $this->actingAs(hdBailleur())
        ->post(route('bailleur.annonces.store'), $donnees)
        ->assertSessionHasErrors('details_hotel.arrivee');
});

test('les détails d\'hôtel sont ignorés pour un autre type de bien', function () {
    $this->actingAs(hdBailleur())
        ->post(route('bailleur.annonces.store'), hdDonnees(['type_bien' => 'chambre', 'periodicite' => 'mensuel']))
        ->assertSessionHasNoErrors();

    expect(Annonce::firstOrFail()->details_hotel)->toBeNull();
});

test('les détails d\'hôtel sont facultatifs', function () {
    $donnees = hdDonnees();
    unset($donnees['details_hotel']);

    $this->actingAs(hdBailleur())
        ->post(route('bailleur.annonces.store'), $donnees)
        ->assertSessionHasNoErrors();

    expect(Annonce::count())->toBe(1);
});

test('les détails publics d\'un hôtel utilisent des libellés lisibles', function () {
    $annonce = new Annonce;
    $annonce->type_bien = TypeBien::Hotel;
    $annonce->details_hotel = [
        'etoiles' => 3,
        'types_chambre' => ['vip'],
        'equipements' => ['wifi', 'inconnu'],
        'paiements' => ['moov_money'],
        'arrivee' => '14:00',
        'depart' => '12:00',
    ];

    expect($annonce->detailsHotelPublics())->toMatchArray([
        'etoiles' => 3,
        'types_chambre' => ['Chambre VIP / suite'],
        'equipements' => ['Wi-Fi'],
        'paiements' => ['Moov Money'],
        'arrivee' => '14:00',
        'depart' => '12:00',
    ]);
});

test('un bien qui n\'est pas un hôtel n\'a pas de détails publics', function () {
    $annonce = new Annonce;
    $annonce->type_bien = TypeBien::Chambre;
    $annonce->details_hotel = ['etoiles' => 3];

    expect($annonce->detailsHotelPublics())->toBeNull();
});
