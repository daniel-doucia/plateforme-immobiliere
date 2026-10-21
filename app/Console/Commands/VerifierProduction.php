<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifierProduction extends Command
{
    protected $signature = 'prod:verifier';

    protected $description = 'Contrôle la configuration de production (avant et après la mise en ligne)';

    private int $erreurs = 0;

    private int $avertissements = 0;

    public function handle(): int
    {
        $this->info('Contrôle de la configuration de production');
        $this->newLine();

        $env = (string) config('app.env');
        $url = (string) config('app.url');
        $fournisseur = (string) config('paiement.fournisseur_par_defaut');

        // Bloquant : ces réglages exposent le site ou ses utilisateurs
        $this->exiger($env === 'production', 'Environnement : production', "APP_ENV vaut « {$env} » : il doit valoir « production ».");
        $this->exiger(! config('app.debug'), 'Mode debug désactivé', 'APP_DEBUG est actif : il expose des informations sensibles. Mettez APP_DEBUG=false.');
        $this->exiger(filled(config('app.key')), "Clé d'application définie", 'APP_KEY est vide : lancez « php artisan key:generate ».');
        $this->exiger(
            str_starts_with($url, 'https://') && ! str_contains($url, 'localhost') && ! str_contains($url, '127.0.0.1'),
            'APP_URL en HTTPS',
            "APP_URL doit être l'adresse publique en https:// (actuellement « {$url} »)."
        );
        $this->exiger(
            ! in_array(config('mail.default'), ['log', 'array'], true),
            "Envoi réel des e-mails",
            'MAIL_MAILER vaut « log » ou « array » : les e-mails ne partiraient jamais. Configurez un serveur SMTP.'
        );
        $this->exiger($fournisseur !== 'faux', 'Le faux fournisseur de paiement est désactivé', 'PAIEMENT_FOURNISSEUR vaut « faux » : ce fournisseur de test est refusé en production.');
        $this->exiger(config('session.secure') === true, 'Cookies de session réservés au HTTPS', 'Mettez SESSION_SECURE_COOKIE=true.');
        $this->exiger(config('session.http_only') !== false, 'Cookies de session inaccessibles à JavaScript', 'Mettez SESSION_HTTP_ONLY=true.');
        $this->exiger(! $this->motDePasseAdminDansEnv(), 'Aucun ADMIN_PASSWORD dans le fichier .env', "ADMIN_PASSWORD traîne encore dans le fichier .env : supprimez cette ligne après la création du compte administrateur.");
        $this->exiger(
            is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
            'Dossiers storage et bootstrap/cache accessibles en écriture',
            'Les dossiers storage et bootstrap/cache doivent être accessibles en écriture par le serveur web.'
        );

        // Avertissements : à examiner, sans bloquer
        $this->recommander($fournisseur !== '', 'Un fournisseur de paiement est configuré', 'Aucun fournisseur de paiement : les bailleurs ne pourront pas payer leurs annonces.');
        $this->recommander(config('database.default') !== 'sqlite', 'Base de données de production', 'SQLite est prévu pour le développement : utilisez MySQL ou MariaDB en production.');
        $this->recommander(in_array(config('session.same_site'), ['lax', 'strict'], true), 'Cookies SameSite', 'Mettez SESSION_SAME_SITE=lax (ou strict).');
        $this->recommander(config('annonces.photo_obligatoire') === true, 'Photo obligatoire pour soumettre', 'La règle « au moins une photo » est désactivée.');
        $this->recommander(extension_loaded('gd'), 'Extension GD disponible', 'Extension GD absente : à installer pour le traitement des photos (réencodage prévu).');

        $this->newLine();

        if ($this->erreurs > 0) {
            $this->error("{$this->erreurs} problème(s) bloquant(s), {$this->avertissements} avertissement(s).");

            return self::FAILURE;
        }

        $this->info($this->avertissements > 0
            ? "Aucun problème bloquant, {$this->avertissements} avertissement(s) à examiner."
            : 'Tout est en ordre.');

        return self::SUCCESS;
    }

    private function exiger(bool $ok, string $succes, string $echec): void
    {
        if ($ok) {
            $this->line("  <fg=green>✔</> {$succes}");

            return;
        }

        $this->erreurs++;
        $this->line("  <fg=red>✘</> {$echec}");
    }

    private function recommander(bool $ok, string $succes, string $avertissement): void
    {
        if ($ok) {
            $this->line("  <fg=green>✔</> {$succes}");

            return;
        }

        $this->avertissements++;
        $this->line("  <fg=yellow>!</> {$avertissement}");
    }

    /** Une ligne ADMIN_PASSWORD renseignée (les lignes commentées ou vides sont ignorées). */
    private function motDePasseAdminDansEnv(): bool
    {
        $fichier = config('production.fichier_env');

        if (! is_string($fichier) || ! is_file($fichier)) {
            return false;
        }

        foreach (file($fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $ligne) {
            if (preg_match('/^\s*ADMIN_PASSWORD\s*=\s*\S/', $ligne)) {
                return true;
            }
        }

        return false;
    }
}
