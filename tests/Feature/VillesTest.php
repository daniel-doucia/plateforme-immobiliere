<?php

use App\Models\Annonce;
use App\Models\Quartier;
use App\Models\Ville;
use Database\Seeders\LocalisationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('la recherche publique propose toutes les villes, même sans quartier ni annonce', function () {
    Ville::create(['nom' => 'Moundou']);
    Ville::create(['nom' => 'Bol']);

    $this->get(route('annonces.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('villes', 2)
            ->where('villes.0.nom', 'Bol'));
});

test('filtrer sur une ville sans annonce ne provoque aucune erreur', function () {
    $bol = Ville::create(['nom' => 'Bol']);
    Annonce::factory()->publiee()->create();

    $this->get(route('annonces.index', ['ville_id' => $bol->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 0));
});

test('le seeder ajoute les villes du Tchad avec un quartier par défaut, sans toucher à N\'Djaména', function () {
    $this->seed(LocalisationSeeder::class);
    $this->seed(LocalisationSeeder::class);

    $ndjamena = Ville::where('nom', "N'Djaména")->first();

    expect(Ville::where('nom', "N'Djaména")->count())->toBe(1)
        ->and($ndjamena->quartiers()->count())->toBe(10)
        ->and(Ville::count())->toBeGreaterThan(25);

    foreach (['Moundou', 'Bol', 'Faya-Largeau'] as $nom) {
        $ville = Ville::where('nom', $nom)->first();

        expect($ville)->not->toBeNull()
            ->and($ville->quartiers()->pluck('nom')->all())->toBe(['Centre-ville']);
    }
});

test('une ville dont l\'administrateur a déjà saisi des quartiers ne reçoit pas Centre-ville', function () {
    $moundou = Ville::create(['nom' => 'Moundou']);
    Quartier::create(['ville_id' => $moundou->id, 'nom' => 'Dombao']);

    $this->seed(LocalisationSeeder::class);

    expect($moundou->quartiers()->pluck('nom')->all())->toBe(['Dombao']);
});
