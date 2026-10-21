<?php

/**
 * Configure une production correcte ; le fichier .env simulé est supprimé après chaque test.
 * On ne touche jamais à « database.default » : cela perturberait le nettoyage de la base de test.
 */
function prodConfigurerBonneConfig(string $contenuEnv = "ADMIN_EMAIL=moi@exemple.td\n# ADMIN_PASSWORD=ancien\nADMIN_PASSWORD=\n"): void
{
    $fichier = storage_path('framework/testing/env-'.uniqid());
    file_put_contents($fichier, $contenuEnv);

    config([
        'app.env' => 'production',
        'app.debug' => false,
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        'app.url' => 'https://immotchad.example',
        'mail.default' => 'smtp',
        'paiement.fournisseur_par_defaut' => 'airtel',
        'session.secure' => true,
        'session.http_only' => true,
        'session.same_site' => 'lax',
        'annonces.photo_obligatoire' => true,
        'production.fichier_env' => $fichier,
    ]);
}

afterEach(function () {
    foreach (glob(storage_path('framework/testing/env-*')) ?: [] as $fichier) {
        @unlink($fichier);
    }
});

test('la configuration de développement est refusée', function () {
    $this->artisan('prod:verifier')->assertFailed();
});

test('une configuration de production correcte est acceptée', function () {
    prodConfigurerBonneConfig();

    $this->artisan('prod:verifier')->assertSuccessful();
});

test('un seul réglage dangereux suffit à faire échouer le contrôle', function (string $cle, mixed $valeur) {
    prodConfigurerBonneConfig();
    config([$cle => $valeur]);

    $this->artisan('prod:verifier')->assertFailed();
})->with([
    'debug activé' => ['app.debug', true],
    'clé absente' => ['app.key', ''],
    'URL sans https' => ['app.url', 'http://immotchad.example'],
    'URL locale' => ['app.url', 'https://localhost'],
    'e-mails dans le journal' => ['mail.default', 'log'],
    'faux fournisseur de paiement' => ['paiement.fournisseur_par_defaut', 'faux'],
    'cookie non sécurisé' => ['session.secure', false],
    'cookie lisible par JavaScript' => ['session.http_only', false],
    'mauvais environnement' => ['app.env', 'local'],
]);

test('un mot de passe administrateur oublié dans le fichier .env fait échouer le contrôle', function () {
    prodConfigurerBonneConfig("ADMIN_EMAIL=moi@exemple.td\nADMIN_PASSWORD=\"secret-oublie\"\n");

    $this->artisan('prod:verifier')->assertFailed();
});

test('SQLite en production est un avertissement et non une erreur', function () {
    // La base de test est SQLite : le contrôle doit le signaler sans échouer
    prodConfigurerBonneConfig();

    $this->artisan('prod:verifier')->assertSuccessful();
});

test('l\'absence de fournisseur de paiement est un avertissement et non une erreur', function () {
    prodConfigurerBonneConfig();
    config(['paiement.fournisseur_par_defaut' => '']);

    $this->artisan('prod:verifier')->assertSuccessful();
});
