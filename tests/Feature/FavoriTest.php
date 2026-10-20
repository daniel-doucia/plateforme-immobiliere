<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use App\Models\Favori;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function favorisUtilisateur(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Client->value);

    return $user;
}

test('un visiteur ne peut pas gérer de favoris', function () {
    $annonce = Annonce::factory()->publiee()->create();

    $this->post(route('favoris.store', $annonce->slug))->assertRedirect('/login');
    $this->get(route('favoris.index'))->assertRedirect('/login');

    expect(Favori::count())->toBe(0);
});

test('on ajoute une annonce publiée en favori, sans doublon', function () {
    $user = favorisUtilisateur();
    $annonce = Annonce::factory()->publiee()->create();

    $this->actingAs($user)->post(route('favoris.store', $annonce->slug))->assertRedirect();
    $this->actingAs($user)->post(route('favoris.store', $annonce->slug))->assertRedirect();

    expect(Favori::count())->toBe(1)
        ->and(Favori::first()->user_id)->toBe($user->id);
});

test('on ne peut pas mettre en favori une annonce non publiée', function () {
    $user = favorisUtilisateur();
    $brouillon = Annonce::factory()->create();
    $expiree = Annonce::factory()->expiree()->create();
    $cloturee = Annonce::factory()->publiee()->create();
    $cloturee->forceFill(['statut' => StatutAnnonce::Cloturee])->save();

    foreach ([$brouillon, $expiree, $cloturee] as $annonce) {
        $this->actingAs($user)->post(route('favoris.store', $annonce->slug))->assertNotFound();
    }

    expect(Favori::count())->toBe(0);
});

test('on retire un favori', function () {
    $user = favorisUtilisateur();
    $annonce = Annonce::factory()->publiee()->create();
    Favori::create(['user_id' => $user->id, 'annonce_id' => $annonce->id]);

    $this->actingAs($user)->delete(route('favoris.destroy', $annonce->slug))->assertRedirect();

    expect(Favori::count())->toBe(0);
});

test('on ne peut pas retirer le favori d\'un autre utilisateur', function () {
    $proprietaire = favorisUtilisateur();
    $annonce = Annonce::factory()->publiee()->create();
    Favori::create(['user_id' => $proprietaire->id, 'annonce_id' => $annonce->id]);

    $this->actingAs(favorisUtilisateur())->delete(route('favoris.destroy', $annonce->slug))->assertRedirect();

    expect(Favori::count())->toBe(1);
});

test('la liste ne montre que mes favoris et signale les annonces indisponibles', function () {
    $moi = favorisUtilisateur();
    $autre = favorisUtilisateur();
    $expiree = Annonce::factory()->expiree()->create();
    $visible = Annonce::factory()->publiee()->create();

    Favori::create(['user_id' => $moi->id, 'annonce_id' => $expiree->id]);
    Favori::create(['user_id' => $moi->id, 'annonce_id' => $visible->id]);
    Favori::create(['user_id' => $autre->id, 'annonce_id' => $visible->id]);

    $this->actingAs($moi)->get(route('favoris.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Favoris/Index')
            ->has('favoris.data', 2)
            ->where('favoris.data.0.disponible', true)
            ->where('favoris.data.1.disponible', false)
            ->missing('favoris.data.1.prix'));
});

test('la fiche indique si l\'annonce est en favori', function () {
    $user = favorisUtilisateur();
    $annonce = Annonce::factory()->publiee()->create();

    $this->get(route('annonces.show', $annonce->slug))
        ->assertInertia(fn (Assert $page) => $page->where('est_favori', false)->where('slug', $annonce->slug));

    $this->actingAs($user)->get(route('annonces.show', $annonce->slug))
        ->assertInertia(fn (Assert $page) => $page->where('est_favori', false));

    Favori::create(['user_id' => $user->id, 'annonce_id' => $annonce->id]);

    $this->actingAs($user)->get(route('annonces.show', $annonce->slug))
        ->assertInertia(fn (Assert $page) => $page->where('est_favori', true));
});

test('le nombre de favoris est limité', function () {
    config(['annonces.max_favoris' => 1]);
    $user = favorisUtilisateur();
    $premiere = Annonce::factory()->publiee()->create();
    $seconde = Annonce::factory()->publiee()->create();

    $this->actingAs($user)->post(route('favoris.store', $premiere->slug))->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('favoris.store', $seconde->slug))->assertSessionHasErrors('favori');

    expect(Favori::count())->toBe(1);
});

test('supprimer un compte supprime ses favoris', function () {
    $user = favorisUtilisateur();
    $annonce = Annonce::factory()->publiee()->create();
    Favori::create(['user_id' => $user->id, 'annonce_id' => $annonce->id]);

    $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password'])->assertRedirect('/');

    expect(User::find($user->id))->toBeNull()
        ->and(Favori::count())->toBe(0);
});
