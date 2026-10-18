<?php

use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('la liste est publique et ne montre que les annonces publiées et non expirées', function () {
    Annonce::factory()->create();            // brouillon
    Annonce::factory()->publiee()->create(); // visible
    Annonce::factory()->expiree()->create(); // publiée mais expirée

    $this->get(route('annonces.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Annonces/Index')
            ->has('annonces.data', 1));
});

test('le filtre par type d\'offre fonctionne', function () {
    Annonce::factory()->publiee()->create(['type_offre' => 'location', 'prix' => 100000]);
    Annonce::factory()->publiee()->create(['type_offre' => 'vente', 'periodicite' => null, 'prix' => 5000000]);

    $this->get(route('annonces.index', ['type_offre' => 'vente']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('annonces.data', 1)
            ->where('annonces.data.0.prix', 5000000));
});

test('le filtre par prix fonctionne', function () {
    Annonce::factory()->publiee()->create(['prix' => 50000]);
    Annonce::factory()->publiee()->create(['prix' => 150000]);
    Annonce::factory()->publiee()->create(['prix' => 400000]);

    $this->get(route('annonces.index', ['prix_min' => 100000, 'prix_max' => 200000]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('annonces.data', 1)
            ->where('annonces.data.0.prix', 150000));
});

test('le filtre par ville fonctionne', function () {
    $moundou = Ville::create(['nom' => 'Moundou']);
    $centre = Quartier::create(['ville_id' => $moundou->id, 'nom' => 'Centre']);

    Annonce::factory()->publiee()->create(); // N'Djaména
    Annonce::factory()->publiee()->create(['quartier_id' => $centre->id]);

    $this->get(route('annonces.index', ['ville_id' => $moundou->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('annonces.data', 1)
            ->where('annonces.data.0.lieu', 'Centre, Moundou'));
});

test('la recherche par mot fonctionne', function () {
    Annonce::factory()->publiee()->create(['titre' => 'Villa avec piscine']);
    Annonce::factory()->publiee()->create(['titre' => 'Studio meublé']);

    $this->get(route('annonces.index', ['q' => 'piscine']))
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 1));
});

test('le tri par prix fonctionne', function () {
    Annonce::factory()->publiee()->create(['prix' => 300000]);
    Annonce::factory()->publiee()->create(['prix' => 100000]);

    $this->get(route('annonces.index', ['tri' => 'prix_asc']))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.prix', 100000));

    $this->get(route('annonces.index', ['tri' => 'prix_desc']))
        ->assertInertia(fn (Assert $page) => $page->where('annonces.data.0.prix', 300000));
});

test('un paramètre invalide est ignoré sans erreur', function () {
    Annonce::factory()->publiee()->count(2)->create();

    $this->get(route('annonces.index', ['type_bien' => 'chateau', 'prix_min' => 'abc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 2));
});

test('la liste est paginée par 12', function () {
    Annonce::factory()->publiee()->count(13)->create();

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 12));
});

test('une annonce publiée a une fiche publique', function () {
    $annonce = Annonce::factory()->publiee()->create();

    $this->get(route('annonces.show', $annonce->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Annonces/Show')
            ->where('annonce.titre', $annonce->titre));
});

test('une annonce non publiée n\'a pas de fiche publique', function () {
    $brouillon = Annonce::factory()->create();
    $expiree = Annonce::factory()->expiree()->create();
    $rejetee = Annonce::factory()->create();
    $rejetee->forceFill(['statut' => StatutAnnonce::Rejetee])->save();
    $cloturee = Annonce::factory()->publiee()->create();
    $cloturee->forceFill(['statut' => StatutAnnonce::Cloturee])->save();

    foreach ([$brouillon, $expiree, $rejetee, $cloturee] as $annonce) {
        $this->get(route('annonces.show', $annonce->slug))->assertNotFound();
    }
});

test('un slug inconnu donne une erreur 404', function () {
    $this->get('/annonces/inconnue-abc123')->assertNotFound();
});

test('le téléphone du bailleur est caché aux visiteurs', function () {
    $bailleur = User::factory()->create(['telephone' => '+23599887766']);
    $annonce = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);

    $this->get(route('annonces.show', $annonce->slug))
        ->assertOk()
        ->assertDontSee('23599887766')
        ->assertInertia(fn (Assert $page) => $page->where('peut_voir_contact', false));
});

test('un utilisateur connecté voit le téléphone du bailleur', function () {
    $bailleur = User::factory()->create(['telephone' => '+23599887766']);
    $annonce = Annonce::factory()->publiee()->create(['user_id' => $bailleur->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('annonces.show', $annonce->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->where('peut_voir_contact', true)
            ->where('contact.telephone', '+23599887766'));
});

test('les vues sont comptées sauf celles du propriétaire', function () {
    $annonce = Annonce::factory()->publiee()->create();

    $this->get(route('annonces.show', $annonce->slug));
    expect($annonce->fresh()->vues_count)->toBe(1);

    $this->actingAs($annonce->user)->get(route('annonces.show', $annonce->slug));
    expect($annonce->fresh()->vues_count)->toBe(1);
});
