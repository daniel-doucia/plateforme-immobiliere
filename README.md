# Plateforme immobilière

Application web qui met en relation les propriétaires et bailleurs avec des clients à la recherche d'un logement. Développée avec Laravel, Inertia.js et Vue.js.

## Description
Les propriétaires et bailleurs publient des annonces pour vendre ou louer leurs maisons, ou louer des chambres. Les clients consultent les annonces disponibles.

## Fonctionnalités
- Authentification des utilisateurs (inscription et connexion)
- Publication et gestion des annonces (ajout, modification, suppression)
- Annonces de maisons à vendre ou à louer
- Annonces de chambres à louer
- Consultation des annonces par les clients
- Recherche avec filtres (par exemple prix, quartier, type de bien)
- Tableau de bord pour les propriétaires et bailleurs

## Technologies
- Backend : Laravel, PHP, MySQL
- Frontend : Vue.js 3, Inertia.js, HTML, CSS, JavaScript

## Installation
1. git clone https://github.com/daniel-doucia/plateforme-immobiliere.git
2. composer install
3. npm install
4. Copier .env.example vers .env et configurer la base de données
5. php artisan key:generate
6. php artisan migrate
7. npm run dev
8. php artisan serve

## Auteur
Daniel Doucia | linkedin.com/in/daniel-doucia
