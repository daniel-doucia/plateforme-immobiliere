<?php

use App\Models\Quartier;
use App\Models\Ville;
use Database\Seeders\LocalisationSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('le seeder crée les villes et reste idempotent', function () {
    $this->seed(LocalisationSeeder::class);
    $this->seed(LocalisationSeeder::class);

    $ndjamena = Ville::where('nom', "N'Djaména")->get();

    expect($ndjamena)->toHaveCount(1)
        ->and(Ville::count())->toBeGreaterThan(5)
        ->and($ndjamena->first()->quartiers()->count())->toBeGreaterThan(0);
});

test('le slug est généré automatiquement', function () {
    $ville = Ville::create(['nom' => 'Abéché']);

    expect($ville->slug)->toBe('abeche');
});

test('deux quartiers de même nom dans la même ville sont refusés', function () {
    $ville = Ville::create(['nom' => 'Moundou']);
    Quartier::create(['ville_id' => $ville->id, 'nom' => 'Centre']);

    Quartier::create(['ville_id' => $ville->id, 'nom' => 'Centre']);
})->throws(QueryException::class);

test('le même nom de quartier est permis dans deux villes différentes', function () {
    $a = Ville::create(['nom' => 'Moundou']);
    $b = Ville::create(['nom' => 'Sarh']);

    Quartier::create(['ville_id' => $a->id, 'nom' => 'Centre']);
    Quartier::create(['ville_id' => $b->id, 'nom' => 'Centre']);

    expect(Quartier::where('nom', 'Centre')->count())->toBe(2);
});
