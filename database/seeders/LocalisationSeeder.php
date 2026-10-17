<?php

namespace Database\Seeders;

use App\Models\Quartier;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class LocalisationSeeder extends Seeder
{
    public function run(): void
    {
        $villes = [
            "N'Djaména", 'Moundou', 'Sarh', 'Abéché', 'Doba', 'Bongor',
            'Pala', 'Kélo', 'Mongo', 'Koumra', 'Am Timan', 'Ati',
        ];

        foreach ($villes as $nom) {
            Ville::firstOrCreate(['nom' => $nom]);
        }

        // Quartiers de départ pour N'Djaména (arrondissements à compléter par l'admin)
        $ndjamena = Ville::where('nom', "N'Djaména")->firstOrFail();

        $quartiers = [
            'Moursal', 'Farcha', 'Chagoua', 'Diguel', 'Klemat',
            'Walia', 'Dembé', 'Amriguébé', 'Ambassatna', 'Gassi',
        ];

        foreach ($quartiers as $nom) {
            Quartier::firstOrCreate(['ville_id' => $ndjamena->id, 'nom' => $nom]);
        }
    }
}
