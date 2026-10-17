<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

test('un client ne peut pas accéder à l\'administration', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Client->value);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('un visiteur non connecté est redirigé vers la connexion', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('un administrateur accède à l\'administration', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)->get('/admin')->assertOk();
});

test('une inscription attribue le rôle client', function () {
    $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'test@example.com')->first()->hasRole('client'))->toBeTrue();
});
