<?php

use App\Models\Annonce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('le filtre par nombre de chambres minimum fonctionne', function () {
    Annonce::factory()->publiee()->create(['nb_chambres' => 1]);
    Annonce::factory()->publiee()->create(['nb_chambres' => 3]);
    Annonce::factory()->publiee()->create(['nb_chambres' => 4]);

    $this->get(route('annonces.index', ['chambres_min' => 3]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('annonces.data', 2)
            ->where('filtres.chambres_min', fn ($valeur) => (int) $valeur === 3));
});

test('un nombre de chambres invalide est ignoré sans erreur', function () {
    Annonce::factory()->publiee()->count(2)->create();

    $this->get(route('annonces.index', ['chambres_min' => 'beaucoup']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('annonces.data', 2));
});
