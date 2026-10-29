<?php

use App\Models\MessageContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function contactDonneesValides(array $surcharge = []): array
{
    return array_merge([
        'nom' => 'Awa Mahamat',
        'email' => 'awa@example.com',
        'telephone' => '+235 66 00 00 00',
        'sujet' => 'Question sur une annonce',
        'message' => 'Bonjour, je voudrais des informations sur une chambre.',
        'site_web' => '',
    ], $surcharge);
}

test('la page à propos est accessible à tous', function () {
    $this->get(route('apropos'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Apropos')
            ->where('contact.zone', 'Tchad')
            ->where('contact.email', 'danieldoucia.pro@gmail.com')
        );
});

test('la page contact est accessible à tous et affiche les coordonnées', function () {
    $this->get(route('contact.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contact')
            ->has('contact.telephones', 2)
        );
});

test('un message valide est enregistré en base', function () {
    $this->post(route('contact.store'), contactDonneesValides())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('messages_contact', [
        'nom' => 'Awa Mahamat',
        'email' => 'awa@example.com',
        'sujet' => 'Question sur une annonce',
        'lu_le' => null,
    ]);
});

test('le téléphone est facultatif', function () {
    $this->post(route('contact.store'), contactDonneesValides(['telephone' => '']))
        ->assertSessionHasNoErrors();

    expect(MessageContact::count())->toBe(1);
});

test('les champs obligatoires sont exigés', function () {
    $this->post(route('contact.store'), [])
        ->assertSessionHasErrors(['nom', 'email', 'sujet', 'message']);

    expect(MessageContact::count())->toBe(0);
});

test('une adresse e-mail invalide est refusée', function () {
    $this->post(route('contact.store'), contactDonneesValides(['email' => 'pas-un-email']))
        ->assertSessionHasErrors('email');
});

test('un message trop court ou trop long est refusé', function () {
    $this->post(route('contact.store'), contactDonneesValides(['message' => 'court']))
        ->assertSessionHasErrors('message');

    $this->post(route('contact.store'), contactDonneesValides(['message' => str_repeat('a', 3001)]))
        ->assertSessionHasErrors('message');

    expect(MessageContact::count())->toBe(0);
});

test('un numéro de téléphone invalide est refusé', function () {
    $this->post(route('contact.store'), contactDonneesValides(['telephone' => 'abc<script>']))
        ->assertSessionHasErrors('telephone');
});

test('le champ piège rempli par un robot n’enregistre rien', function () {
    $this->post(route('contact.store'), contactDonneesValides(['site_web' => 'http://spam.example']))
        ->assertSessionHasNoErrors();

    expect(MessageContact::count())->toBe(0);
});

test('un visiteur ne peut pas marquer son message comme lu', function () {
    $this->post(route('contact.store'), contactDonneesValides(['lu_le' => now()->toDateTimeString()]))
        ->assertSessionHasNoErrors();

    expect(MessageContact::first()->lu_le)->toBeNull();
});

test('les envois abusifs sont limités', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('contact.store'), contactDonneesValides())->assertSessionHasNoErrors();
    }

    $this->post(route('contact.store'), contactDonneesValides())->assertStatus(429);

    expect(MessageContact::count())->toBe(5);
});
