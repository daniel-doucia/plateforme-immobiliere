<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('les en-têtes de sécurité sont présents sur les pages', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
});

test('les pages d\'erreur ont aussi les en-têtes de sécurité', function () {
    $this->get('/page-qui-n-existe-pas')
        ->assertNotFound()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('HSTS n\'est pas envoyé en HTTP', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
});

test('HSTS est envoyé en HTTPS', function () {
    $this->get('https://localhost/')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});
