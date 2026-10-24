<?php

namespace Database\Seeders;

use App\Models\Forfait;
use Illuminate\Database\Seeder;

class ForfaitSeeder extends Seeder
{
    public function run(): void
    {
        // type de bien => [durée en jours => prix en FCFA] (valeurs d'exemple, modifiables dans l'administration)
        $grille = [
            'chambre' => [30 => 2000, 60 => 3500],
            'appartement' => [30 => 5000, 60 => 9000],
            'maison' => [30 => 5000, 60 => 9000],
            'terrain' => [30 => 10000, 60 => 18000],
            'hotel' => [30 => 10000, 60 => 18000],
        ];

        foreach ($grille as $type => $durees) {
            foreach ($durees as $jours => $prix) {
                Forfait::firstOrCreate(
                    ['type_bien' => $type, 'duree_jours' => $jours],
                    [
                        'nom' => ucfirst($type).' · '.$jours.' jours',
                        'prix' => $prix,
                        'actif' => true,
                        'ordre' => $jours,
                    ]
                );
            }
        }
    }
}
