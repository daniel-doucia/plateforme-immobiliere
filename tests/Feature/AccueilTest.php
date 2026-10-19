<?php

use App\Models\Annonce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('la page d\'accueil est publique et affiche les annonces publiées', function () {
    Annonce::factory()->publiee()->create();
    Annonce::factory()->create(); // brouillon : invisible

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Annonces/Index')
            ->has('annonces.data', 1));
});
