<?php

use App\Enums\Role;
use App\Models\AlerteRecherche;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function alerteUtilisateur(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Client->value);

    return $user;
}

function alerteExistante(User $user, array $surcharge = []): AlerteRecherche
{
    return AlerteRecherche::create(array_merge([
        'user_id' => $user->id,
        'type_offre' => 'location',
        'type_bien' => 'chambre',
        'actif' => true,
        'derniere_verification_le' => now()->subDay(),
    ], $surcharge));
}

test('un visiteur ne peut pas gérer d\'alertes', function () {
    $this->get(route('alertes.index'))->assertRedirect('/login');
    $this->post(route('alertes.store'), ['type_bien' => 'chambre'])->assertRedirect('/login');

    expect(AlerteRecherche::count())->toBe(0);
});

test('on crée une alerte à partir de critères', function () {
    $user = alerteUtilisateur();

    $this->actingAs($user)
        ->post(route('alertes.store'), ['type_offre' => 'location', 'type_bien' => 'chambre', 'prix_max' => 60000])
        ->assertRedirect(route('alertes.index'));

    $alerte = AlerteRecherche::first();

    expect(AlerteRecherche::count())->toBe(1)
        ->and($alerte->user_id)->toBe($user->id)
        ->and($alerte->prix_max)->toBe(60000)
        ->and($alerte->actif)->toBeTrue()
        ->and($alerte->derniere_verification_le)->not->toBeNull();
});

test('une alerte sans aucun critère est refusée', function () {
    $this->actingAs(alerteUtilisateur())
        ->post(route('alertes.store'), [])
        ->assertSessionHasErrors('alerte');

    expect(AlerteRecherche::count())->toBe(0);
});

test('un critère invalide est refusé', function () {
    $this->actingAs(alerteUtilisateur())
        ->post(route('alertes.store'), ['type_bien' => 'chateau'])
        ->assertSessionHasErrors('type_bien');

    expect(AlerteRecherche::count())->toBe(0);
});

test('un prix minimum supérieur au prix maximum est refusé', function () {
    $this->actingAs(alerteUtilisateur())
        ->post(route('alertes.store'), ['prix_min' => 90000, 'prix_max' => 10000])
        ->assertSessionHasErrors('prix_max');

    expect(AlerteRecherche::count())->toBe(0);
});

test('un quartier qui n\'appartient pas à la ville est refusé', function () {
    $moundou = Ville::create(['nom' => 'Moundou']);
    $sarh = Ville::create(['nom' => 'Sarh']);
    $centre = Quartier::create(['ville_id' => $sarh->id, 'nom' => 'Centre']);

    $this->actingAs(alerteUtilisateur())
        ->post(route('alertes.store'), ['ville_id' => $moundou->id, 'quartier_id' => $centre->id])
        ->assertSessionHasErrors('quartier_id');

    expect(AlerteRecherche::count())->toBe(0);
});

test('choisir un quartier fixe aussi sa ville', function () {
    $sarh = Ville::create(['nom' => 'Sarh']);
    $centre = Quartier::create(['ville_id' => $sarh->id, 'nom' => 'Centre']);

    $this->actingAs(alerteUtilisateur())
        ->post(route('alertes.store'), ['quartier_id' => $centre->id])
        ->assertSessionHasNoErrors();

    expect(AlerteRecherche::first()->ville_id)->toBe($sarh->id);
});

test('une alerte identique n\'est pas créée deux fois', function () {
    $user = alerteUtilisateur();

    $this->actingAs($user)->post(route('alertes.store'), ['type_bien' => 'chambre', 'prix_max' => 50000]);
    $this->actingAs($user)->post(route('alertes.store'), ['type_bien' => 'chambre', 'prix_max' => 50000])
        ->assertRedirect(route('alertes.index'));

    expect(AlerteRecherche::count())->toBe(1);
});

test('le nombre d\'alertes est limité', function () {
    config(['annonces.max_alertes' => 1]);
    $user = alerteUtilisateur();

    $this->actingAs($user)->post(route('alertes.store'), ['type_bien' => 'chambre'])->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('alertes.store'), ['type_bien' => 'terrain'])->assertSessionHasErrors('alerte');

    expect(AlerteRecherche::count())->toBe(1);
});

test('la liste ne montre que mes alertes, avec un résumé lisible', function () {
    $moi = alerteUtilisateur();
    alerteExistante($moi);
    alerteExistante(alerteUtilisateur(), ['type_bien' => 'terrain']);

    $this->actingAs($moi)->get(route('alertes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Alertes/Index')
            ->has('alertes', 1)
            ->where('alertes.0.resume', fn ($resume) => str_contains($resume, 'Chambre') && str_contains($resume, 'À louer')));
});

test('on met une alerte en pause puis on la réactive', function () {
    $user = alerteUtilisateur();
    $alerte = alerteExistante($user);

    $this->actingAs($user)->patch(route('alertes.toggle', $alerte->id))->assertRedirect();
    expect($alerte->fresh()->actif)->toBeFalse();

    $ancienne = $alerte->fresh()->derniere_verification_le;

    $this->actingAs($user)->patch(route('alertes.toggle', $alerte->id))->assertRedirect();

    expect($alerte->fresh()->actif)->toBeTrue()
        ->and($alerte->fresh()->derniere_verification_le->greaterThan($ancienne))->toBeTrue();
});

test('on ne peut pas modifier l\'alerte d\'un autre', function () {
    $alerte = alerteExistante(alerteUtilisateur());

    $this->actingAs(alerteUtilisateur())->patch(route('alertes.toggle', $alerte->id))->assertNotFound();

    expect($alerte->fresh()->actif)->toBeTrue();
});

test('on supprime sa propre alerte', function () {
    $user = alerteUtilisateur();
    $alerte = alerteExistante($user);

    $this->actingAs($user)->delete(route('alertes.destroy', $alerte->id))->assertRedirect();

    expect(AlerteRecherche::count())->toBe(0);
});

test('on ne peut pas supprimer l\'alerte d\'un autre', function () {
    $alerte = alerteExistante(alerteUtilisateur());

    $this->actingAs(alerteUtilisateur())->delete(route('alertes.destroy', $alerte->id))->assertNotFound();

    expect(AlerteRecherche::count())->toBe(1);
});
