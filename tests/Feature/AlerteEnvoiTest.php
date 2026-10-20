<?php

use App\Enums\TypeBien;
use App\Models\AlerteRecherche;
use App\Models\Annonce;
use App\Models\User;
use App\Notifications\NouvellesAnnoncesAlerte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function alerteDe(User $user, array $surcharge = []): AlerteRecherche
{
    return AlerteRecherche::create(array_merge([
        'user_id' => $user->id,
        'type_bien' => 'chambre',
        'actif' => true,
        'derniere_verification_le' => now()->subHour(),
    ], $surcharge));
}

function chambrePubliee(array $surcharge = []): Annonce
{
    return Annonce::factory()->publiee()->create(array_merge(['type_bien' => TypeBien::Chambre], $surcharge));
}

test('une nouvelle annonce correspondante déclenche un e-mail', function () {
    Notification::fake();
    $user = User::factory()->create();
    alerteDe($user);
    chambrePubliee();

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertSentTo($user, NouvellesAnnoncesAlerte::class, fn ($n) => $n->total === 1);
});

test('une annonce publiée avant la dernière vérification n\'est pas renvoyée', function () {
    Notification::fake();
    alerteDe(User::factory()->create());
    chambrePubliee(['publiee_le' => now()->subHours(3)]);

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertNothingSent();
});

test('ses propres annonces ne déclenchent pas ses alertes', function () {
    Notification::fake();
    $user = User::factory()->create();
    alerteDe($user);
    chambrePubliee(['user_id' => $user->id]);

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertNothingSent();
});

test('une annonce qui ne correspond pas aux critères n\'envoie rien', function () {
    Notification::fake();
    alerteDe(User::factory()->create(), ['type_bien' => 'terrain']);
    alerteDe(User::factory()->create(), ['prix_max' => 40000]);
    chambrePubliee(['prix' => 50000]);

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertNothingSent();
});

test('le filtre par prix inclut les annonces dans la fourchette', function () {
    Notification::fake();
    $user = User::factory()->create();
    alerteDe($user, ['prix_min' => 30000, 'prix_max' => 60000]);
    chambrePubliee(['prix' => 50000]);

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertSentTo($user, NouvellesAnnoncesAlerte::class);
});

test('une alerte en pause n\'envoie rien', function () {
    Notification::fake();
    alerteDe(User::factory()->create(), ['actif' => false]);
    chambrePubliee();

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertNothingSent();
});

test('un deuxième passage ne renvoie pas les mêmes annonces', function () {
    Notification::fake();
    $user = User::factory()->create();
    alerteDe($user);
    chambrePubliee();

    $this->artisan('alertes:envoyer')->assertSuccessful();
    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertSentToTimes($user, NouvellesAnnoncesAlerte::class, 1);
});

test('la date de vérification avance même sans résultat', function () {
    Notification::fake();
    $alerte = alerteDe(User::factory()->create());

    $this->artisan('alertes:envoyer')->assertSuccessful();

    expect($alerte->fresh()->derniere_verification_le->greaterThan(now()->subMinutes(5)))->toBeTrue();
});

test('les annonces non publiées ou expirées sont ignorées', function () {
    Notification::fake();
    alerteDe(User::factory()->create());
    Annonce::factory()->create(['type_bien' => TypeBien::Chambre]); // brouillon
    chambrePubliee(['expire_le' => now()->subMinute()]);            // publiée mais expirée

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertNothingSent();
});

test('l\'e-mail liste cinq annonces au plus et indique le total', function () {
    Notification::fake();
    $user = User::factory()->create();
    alerteDe($user);
    Annonce::factory()->publiee()->count(7)->create(['type_bien' => TypeBien::Chambre]);

    $this->artisan('alertes:envoyer')->assertSuccessful();

    Notification::assertSentTo(
        $user,
        NouvellesAnnoncesAlerte::class,
        fn ($n) => $n->total === 7 && $n->annonces->count() === 5
    );
});

test('le contenu de l\'e-mail contient le lien de l\'annonce', function () {
    $user = User::factory()->create();
    $alerte = alerteDe($user, ['prix_max' => 60000]);
    $annonce = chambrePubliee(['prix' => 50000]);

    $mail = (new NouvellesAnnoncesAlerte($alerte, collect([$annonce]), 1))->toMail($user);

    expect($mail->subject)->toBe('1 nouvelle annonce pour votre alerte')
        ->and(implode("\n", $mail->introLines))->toContain($annonce->slug)
        ->and($mail->actionUrl)->toContain('type_bien=chambre');
});
