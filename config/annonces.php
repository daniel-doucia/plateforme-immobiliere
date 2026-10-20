<?php

return [
    // Une annonce ne peut être soumise que si elle a au moins une photo
    'photo_obligatoire' => env('ANNONCES_PHOTO_OBLIGATOIRE', true),

    // Limites par compte, pour éviter les abus
    'max_favoris' => 200,
    'max_alertes' => 10,
];
