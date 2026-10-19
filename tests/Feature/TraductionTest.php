<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr']);
    app()->setLocale('fr');
});

test('les traductions françaises des messages utilisés existent', function () {
    foreach ([
        'auth.failed', 'auth.password', 'auth.throttle',
        'passwords.reset', 'passwords.sent', 'passwords.throttled', 'passwords.token', 'passwords.user',
        'validation.required', 'validation.email', 'validation.unique', 'validation.confirmed',
        'validation.min.string', 'validation.max.string', 'validation.max.file', 'validation.mimes',
        'validation.dimensions', 'validation.uploaded', 'validation.regex', 'validation.enum',
        'validation.exists', 'validation.integer', 'validation.attributes.email',
    ] as $cle) {
        expect(trans($cle))->not->toBe($cle);
    }
});

test('une connexion refusée affiche un message en français', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->post('/login', ['email' => 'test@example.com', 'password' => 'mauvais-mot-de-passe'])
        ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
});

test('les messages de validation sont en français avec des noms de champs lisibles', function () {
    $erreur = Validator::make(['email' => ''], ['email' => 'required'])->errors()->first('email');
    expect($erreur)->toBe('Le champ e-mail est obligatoire.');

    $mdp = Validator::make(['password' => 'abc'], ['password' => [Password::min(8)]])->errors()->first('password');
    expect($mdp)->toContain('8 caractères');
});
