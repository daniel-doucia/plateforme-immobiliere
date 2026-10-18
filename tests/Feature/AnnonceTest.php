<?php

use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('une nouvelle annonce est un brouillon avec un slug unique', function () {
    $a = Annonce::factory()->create(['titre' => 'Belle villa à Moursal']);
    $b = Annonce::factory()->create(['titre' => 'Belle villa à Moursal']);

    expect($a->statut)->toBe(StatutAnnonce::Brouillon)
        ->and($a->slug)->toStartWith('belle-villa-a-moursal-')
        ->and($a->slug)->not->toBe($b->slug);
});

test('le statut, le slug et le propriétaire ne peuvent pas être imposés par assignation de masse', function () {
    $modele = Annonce::factory()->create();

    $annonce = new Annonce([
        'quartier_id' => $modele->quartier_id,
        'titre' => 'Annonce pirate',
        'description' => 'Description',
        'type_bien' => 'maison',
        'type_offre' => 'vente',
        'prix' => 1000000,
        // Champs qu'un formulaire malveillant tenterait d'imposer :
        'statut' => 'publiee',
        'slug' => 'pirate',
        'user_id' => 999999,
    ]);

    // Le propriétaire est toujours défini par le code, jamais par le formulaire
    $annonce->user_id = $modele->user_id;
    $annonce->save();

    expect($annonce->statut)->toBe(StatutAnnonce::Brouillon)
        ->and($annonce->slug)->not->toBe('pirate')
        ->and($annonce->user_id)->toBe($modele->user_id);
});

test('une transition valide est acceptée', function () {
    $annonce = Annonce::factory()->create();

    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement);
});

test('publier un brouillon sans paiement ni modération est interdit', function () {
    $annonce = Annonce::factory()->create();

    expect(fn () => $annonce->transitionner(StatutAnnonce::Publiee))
        ->toThrow(DomainException::class);

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('le cycle de vie complet, du brouillon au renouvellement', function () {
    $annonce = Annonce::factory()->create();

    foreach ([
        StatutAnnonce::EnAttentePaiement,
        StatutAnnonce::EnModeration,
        StatutAnnonce::Publiee,
        StatutAnnonce::Expiree,
        StatutAnnonce::EnAttentePaiement,
    ] as $etape) {
        $annonce->transitionner($etape);
    }

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::EnAttentePaiement)
        ->and($annonce->publiee_le)->not->toBeNull();
});

test('une annonce rejetée repasse en brouillon pour correction', function () {
    $annonce = Annonce::factory()->create();
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);
    $annonce->transitionner(StatutAnnonce::EnModeration);
    $annonce->transitionner(StatutAnnonce::Rejetee);
    $annonce->transitionner(StatutAnnonce::Brouillon);

    expect($annonce->fresh()->statut)->toBe(StatutAnnonce::Brouillon);
});

test('une annonce clôturée est définitive', function () {
    $annonce = Annonce::factory()->create();
    $annonce->transitionner(StatutAnnonce::Cloturee);

    foreach (StatutAnnonce::cases() as $statut) {
        expect($annonce->statut->peutPasserA($statut))->toBeFalse();
    }
});

test('seules les annonces publiées et non expirées sont visibles', function () {
    Annonce::factory()->create();            // brouillon
    Annonce::factory()->publiee()->create(); // visible
    Annonce::factory()->expiree()->create(); // publiée mais expirée

    expect(Annonce::publiees()->count())->toBe(1);
});
