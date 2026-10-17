<?php

use App\Enums\Role;
use App\Models\BailleurProfil;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function utilisateur(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

test('un client ne peut pas accéder au profil bailleur', function () {
    $this->actingAs(utilisateur(Role::Client))
        ->get(route('bailleur.profil.edit'))
        ->assertForbidden();
});

test('un bailleur envoie sa pièce et le dossier passe en attente', function () {
    $bailleur = utilisateur(Role::Bailleur);

    $this->actingAs($bailleur)->post(route('bailleur.profil.update'), [
        'type' => 'particulier',
        'piece_identite' => UploadedFile::fake()->createWithContent('cni.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==')),
    ])->assertSessionHasNoErrors();

    $profil = BailleurProfil::where('user_id', $bailleur->id)->first();

    expect($profil->piece_identite_path)->not->toBeNull()
        ->and($profil->statut_verification->value)->toBe('en_attente');
    Storage::disk('local')->assertExists($profil->piece_identite_path);
});

test('un fichier dangereux est refusé', function () {
    $this->actingAs(utilisateur(Role::Bailleur))->post(route('bailleur.profil.update'), [
        'type' => 'particulier',
        'piece_identite' => UploadedFile::fake()->create('virus.php', 10),
    ])->assertSessionHasErrors('piece_identite');
});

test('une agence doit indiquer son nom', function () {
    $this->actingAs(utilisateur(Role::Bailleur))->post(route('bailleur.profil.update'), [
        'type' => 'agence',
    ])->assertSessionHasErrors('nom_commercial');
});

test('l\'admin valide un bailleur', function () {
    $admin = utilisateur(Role::Admin);
    $bailleur = utilisateur(Role::Bailleur);
    $profil = BailleurProfil::create(['user_id' => $bailleur->id, 'piece_identite_path' => 'pieces-identite/x.jpg']);

    $this->actingAs($admin)->post(route('admin.verifications.approuver', $profil))->assertRedirect();

    $profil->refresh();
    expect($profil->statut_verification->value)->toBe('verifie')
        ->and($profil->verifie_par)->toBe($admin->id);
});

test('le rejet exige un motif', function () {
    $profil = BailleurProfil::create(['user_id' => utilisateur(Role::Bailleur)->id]);

    $this->actingAs(utilisateur(Role::Admin))
        ->post(route('admin.verifications.rejeter', $profil), [])
        ->assertSessionHasErrors('motif_rejet');
});

test('un bailleur ne peut pas se valider lui-même ni voir une pièce', function () {
    $bailleur = utilisateur(Role::Bailleur);
    $profil = BailleurProfil::create(['user_id' => $bailleur->id, 'piece_identite_path' => 'pieces-identite/x.jpg']);

    $this->actingAs($bailleur)->post(route('admin.verifications.approuver', $profil))->assertForbidden();
    $this->actingAs($bailleur)->get(route('admin.verifications.piece', $profil))->assertForbidden();
});

test('l\'admin peut consulter la pièce', function () {
    Storage::disk('local')->put('pieces-identite/x.jpg', 'contenu');
    $profil = BailleurProfil::create([
        'user_id' => utilisateur(Role::Bailleur)->id,
        'piece_identite_path' => 'pieces-identite/x.jpg',
    ]);

    $this->actingAs(utilisateur(Role::Admin))
        ->get(route('admin.verifications.piece', $profil))
        ->assertOk();
});
