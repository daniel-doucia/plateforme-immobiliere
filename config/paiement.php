<?php

return [
    'fournisseur_par_defaut' => env('PAIEMENT_FOURNISSEUR', 'faux'),

    'faux' => [
        'secret' => env('PAIEMENT_FAUX_SECRET'),
    ],
];
