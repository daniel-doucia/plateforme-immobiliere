<?php

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use App\Models\AnnoncePhoto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

/** Construit un vrai PNG (sans GD) : signature, en-tête, données compressées, fin. */
function pngDeTest(int $largeur = 600, int $hauteur = 450): string
{
    $ligne = "\x00".str_repeat("\x20\x80\xc0", $largeur);
    $brut = str_repeat($ligne, $hauteur);
    $bloc = fn (string $type, string $donnees) => pack('N', strlen($donnees)).$type.$donnees.pack('N', crc32($type.$donnees));

    return "\x89PNG\r\n\x1a\n"
        .$bloc('IHDR', pack('NNCCCCC', $largeur, $hauteur, 8, 2, 0, 0, 0))
        .$bloc('IDAT', gzcompress($brut))
        .$bloc('IEND', '');
}

function fichierPng(string $nom = 'photo.png', int $largeur = 600, int $hauteur = 450): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nom, pngDeTest($largeur, $hauteur));
}

function utilisateurPhoto(Role $role = Role::Bailleur): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function photoStockee(Annonce $annonce, int $ordre = 1): AnnoncePhoto
{
    $chemin = 'annonces/'.$annonce->id.'/'.uniqid().'.png';
    Storage::disk('local')->put($chemin, pngDeTest());

    return AnnoncePhoto::create([
        'annonce_id' => $annonce->id,
        'chemin' => $chemin,
        'mime' => 'image/png',
        'taille' => 1000,
        'largeur' => 600,
        'hauteur' => 450,
        'ordre' => $ordre,
    ]);
}

test('un bailleur ajoute une photo, stockée hors zone publique sous un nom aléatoire', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [fichierPng('ma-photo.png')]])
        ->assertSessionHasNoErrors();

    $photo = AnnoncePhoto::first();

    expect(AnnoncePhoto::count())->toBe(1)
        ->and($photo->chemin)->toStartWith('annonces/'.$annonce->id.'/')
        ->and($photo->chemin)->toEndWith('.png')
        ->and($photo->chemin)->not->toContain('ma-photo')
        ->and($photo->mime)->toBe('image/png')
        ->and($photo->largeur)->toBe(600)
        ->and($photo->hauteur)->toBe(450);

    Storage::disk('local')->assertExists($photo->chemin);
});

test('un faux fichier image est refusé', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $faux = UploadedFile::fake()->createWithContent('faux.png', '<?php echo "pirate"; ?>');

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [$faux]])
        ->assertSessionHasErrors();

    expect(AnnoncePhoto::count())->toBe(0);
});

test('un fichier d\'un autre format est refusé', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $script = UploadedFile::fake()->createWithContent('script.php', pngDeTest());

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [$script]])
        ->assertSessionHasErrors();

    expect(AnnoncePhoto::count())->toBe(0);
});

test('une photo trop lourde est refusée', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), [
            'photos' => [UploadedFile::fake()->create('lourde.png', 4000, 'image/png')],
        ])
        ->assertSessionHasErrors();

    expect(AnnoncePhoto::count())->toBe(0);
});

test('une photo trop petite est refusée', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [fichierPng('petite.png', 200, 150)]])
        ->assertSessionHasErrors();

    expect(AnnoncePhoto::count())->toBe(0);
});

test('le nombre de photos par annonce est limité', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);

    foreach (range(1, Annonce::MAX_PHOTOS) as $ordre) {
        photoStockee($annonce, $ordre);
    }

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [fichierPng()]])
        ->assertSessionHasErrors('photos');

    expect(AnnoncePhoto::count())->toBe(Annonce::MAX_PHOTOS);
});

test('un autre bailleur ne peut pas ajouter de photo', function () {
    $annonce = Annonce::factory()->create(['user_id' => utilisateurPhoto()->id]);

    $this->actingAs(utilisateurPhoto())
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [fichierPng()]])
        ->assertForbidden();

    expect(AnnoncePhoto::count())->toBe(0);
});

test('une annonce qui n\'est plus un brouillon n\'accepte plus de photo', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $annonce->transitionner(StatutAnnonce::EnAttentePaiement);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.store', $annonce), ['photos' => [fichierPng()]])
        ->assertForbidden();
});

test('le bailleur supprime une photo et son fichier', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $photo = photoStockee($annonce);
    $chemin = $photo->chemin;

    $this->actingAs($bailleur)
        ->delete(route('bailleur.annonces.photos.destroy', [$annonce, $photo]))
        ->assertRedirect();

    expect(AnnoncePhoto::count())->toBe(0);
    Storage::disk('local')->assertMissing($chemin);
});

test('on ne peut pas supprimer la photo d\'une autre annonce via la sienne', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $autre = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $photoAutre = photoStockee($autre);

    $this->actingAs($bailleur)
        ->delete(route('bailleur.annonces.photos.destroy', [$annonce, $photoAutre]))
        ->assertNotFound();

    expect(AnnoncePhoto::count())->toBe(1);
});

test('le bailleur choisit la photo principale', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    $premiere = photoStockee($annonce, 1);
    $seconde = photoStockee($annonce, 2);

    $this->actingAs($bailleur)
        ->post(route('bailleur.annonces.photos.principale', [$annonce, $seconde]))
        ->assertRedirect();

    expect($annonce->photos()->pluck('id')->all())->toBe([$seconde->id, $premiere->id]);
});

test('un visiteur voit la photo d\'une annonce publiée, avec des en-têtes sûrs', function () {
    $annonce = Annonce::factory()->publiee()->create();
    $photo = photoStockee($annonce);

    $this->get(route('photos.show', $photo))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('la photo d\'une annonce non publiée n\'est visible que par son propriétaire et les modérateurs', function () {
    $proprietaire = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $proprietaire->id]);
    $photo = photoStockee($annonce);

    $this->get(route('photos.show', $photo))->assertNotFound();
    $this->actingAs(utilisateurPhoto())->get(route('photos.show', $photo))->assertNotFound();
    $this->actingAs($proprietaire)->get(route('photos.show', $photo))->assertOk();
    $this->actingAs(utilisateurPhoto(Role::Moderateur))->get(route('photos.show', $photo))->assertOk();
});

test('la fiche publique expose les photos dans l\'ordre', function () {
    $annonce = Annonce::factory()->publiee()->create();
    photoStockee($annonce, 1);
    photoStockee($annonce, 2);

    $this->get(route('annonces.show', $annonce->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->has('photos', 2)
            ->where('photos.0.url', fn ($url) => str_starts_with($url, '/photos/')));
});

test('la liste publique expose la photo principale', function () {
    $annonce = Annonce::factory()->publiee()->create();
    photoStockee($annonce);

    $this->get(route('annonces.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('annonces.data.0.photo', fn ($url) => str_starts_with($url, '/photos/')));
});

test('la page photos est réservée au propriétaire', function () {
    $bailleur = utilisateurPhoto();
    $annonce = Annonce::factory()->create(['user_id' => $bailleur->id]);
    photoStockee($annonce);

    $this->actingAs($bailleur)
        ->get(route('bailleur.annonces.photos.index', $annonce))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bailleur/Annonces/Photos')
            ->has('photos', 1));

    $this->actingAs(utilisateurPhoto())
        ->get(route('bailleur.annonces.photos.index', $annonce))
        ->assertForbidden();
});
