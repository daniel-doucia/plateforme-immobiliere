# Mise en ligne d'ImmoTchad

## Prérequis du serveur
- PHP 8.2 ou plus, avec les extensions : ctype, curl, dom, fileinfo, gd, mbstring, openssl, pdo_mysql, tokenizer, xml, zip, zlib
- MySQL ou MariaDB, Nginx (ou Caddy, Apache), Composer, Git
- Node 20 ou plus (uniquement pour compiler les pages)
- Un nom de domaine et un certificat HTTPS (Let's Encrypt)
- La racine web doit pointer sur le dossier public/, jamais sur la racine du projet

## Première installation
    git clone https://github.com/daniel-doucia/plateforme-immobiliere.git
    cd plateforme-immobiliere
    git checkout main
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    cp .env.production.example .env
    (remplir le fichier .env, y compris ADMIN_EMAIL et ADMIN_PASSWORD)
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed --force
    (supprimer la ligne ADMIN_PASSWORD du fichier .env)
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan prod:verifier

Le dernier contrôle ne doit signaler aucun problème bloquant.

## Droits sur les dossiers
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rw storage bootstrap/cache

## Tâches planifiées (expiration des annonces, rappels, alertes)
Ajouter cette ligne avec la commande crontab -e, en remplaçant le chemin :

    * * * * * cd /chemin/plateforme-immobiliere && php artisan schedule:run >> /dev/null 2>&1

## Sauvegardes (à automatiser chaque nuit, copie hors du serveur)
- La base de données : mysqldump
- Le dossier storage/app : photos des annonces et pièces d'identité (données sensibles : copie chiffrée)
- Tester la restauration au moins une fois

## Mise à jour
    php artisan down
    git pull
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    php artisan migrate --force
    php artisan config:cache && php artisan route:cache && php artisan view:cache
    php artisan up
    php artisan prod:verifier

## Sécurité à vérifier avant l'ouverture
- APP_DEBUG=false et aucun mot de passe administrateur dans le fichier .env
- HTTPS obligatoire (redirection de http vers https dans la configuration du serveur web)
- Le fichier .env n'est jamais accessible depuis le web
- Un fournisseur de paiement réel est branché (le faux fournisseur est refusé)
