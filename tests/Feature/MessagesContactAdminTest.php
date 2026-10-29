<?php

use App\Models\MessageContact;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function messagesUtilisateur(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function messagesCreer(array $surcharge = []): MessageContact
{
    $message = MessageContact::create(array_merge([
        'nom' => 'Awa Mahamat',
        'email' => 'awa@example.com',
        'telephone' => null,
        'sujet' => 'Bonjour',
        'message' => 'Un message de test suffisamment long.',
    ], $surcharge));

    return $message;
}

test('un visiteur non connecté est renvoyé vers la connexion', function () {
    $this->get(route('admin.messages.index'))->assertRedirect(route('login'));
});

test('les rôles non administrateurs n’accèdent pas aux messages', function (string $role) {
    $message = messagesCreer();
    $utilisateur = messagesUtilisateur($role);

    $this->actingAs($utilisateur)->get(route('admin.messages.index'))->assertForbidden();
    $this->actingAs($utilisateur)->patch(route('admin.messages.lu', $message))->assertForbidden();
    $this->actingAs($utilisateur)->delete(route('admin.messages.destroy', $message))->assertForbidden();

    expect($message->fresh())->not->toBeNull()
        ->and($message->fresh()->lu_le)->toBeNull();
})->with(['client', 'bailleur', 'moderateur']);

test('l’administrateur voit les messages, les non lus d’abord par défaut', function () {
    messagesCreer(['sujet' => 'Non lu']);
    $lu = messagesCreer(['sujet' => 'Déjà lu']);
    $lu->forceFill(['lu_le' => now()])->save();

    $admin = messagesUtilisateur('admin');

    $this->actingAs($admin)->get(route('admin.messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Messages/Index')
            ->where('filtre', 'non_lus')
            ->where('non_lus', 1)
            ->has('messages.data', 1)
            ->where('messages.data.0.sujet', 'Non lu')
        );

    $this->actingAs($admin)->get(route('admin.messages.index', ['filtre' => 'tous']))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 2));
});

test('l’administrateur peut marquer un message lu puis non lu', function () {
    $message = messagesCreer();
    $admin = messagesUtilisateur('admin');

    $this->actingAs($admin)->patch(route('admin.messages.lu', $message))->assertRedirect();
    expect($message->fresh()->lu_le)->not->toBeNull();

    $this->actingAs($admin)->patch(route('admin.messages.lu', $message))->assertRedirect();
    expect($message->fresh()->lu_le)->toBeNull();
});

test('l’administrateur peut supprimer un message', function () {
    $message = messagesCreer();

    $this->actingAs(messagesUtilisateur('admin'))
        ->delete(route('admin.messages.destroy', $message))
        ->assertRedirect();

    $this->assertDatabaseMissing('messages_contact', ['id' => $message->id]);
});
