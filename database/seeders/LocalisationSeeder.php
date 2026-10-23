<?php

namespace Database\Seeders;

use App\Models\Quartier;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class LocalisationSeeder extends Seeder
{
    private const CAPITALE = "N'Djaména";

    /** Chefs-lieux de province et principales villes : l'administrateur peut en ajouter ou en retirer. */
    private const VILLES = [
        "N'Djaména",
        'Abéché', 'Adré', 'Am Timan', 'Am-Djarass', 'Ati', 'Bardaï', 'Bébédja', 'Biltine', 'Bitkine', 'Bol', 'Bongor', 'Bousso',
        'Doba', 'Fada', 'Faya-Largeau', 'Fianga', 'Goz Beïda', 'Kélo', 'Koumra', 'Laï', 'Léré', 'Mao', 'Massakory', 'Massenya',
        'Moundou', 'Mongo', 'Moussoro', 'Oum Hadjer', 'Pala', 'Sarh',
    ];

    private const QUARTIERS_CAPITALE = [
        'Moursal', 'Farcha', 'Chagoua', 'Diguel', 'Klemat',
        'Walia', 'Dembé', 'Amriguébé', 'Ambassatna', 'Gassi',
    ];

    public function run(): void
    {
        foreach (self::VILLES as $nom) {
            $ville = Ville::firstOrCreate(['nom' => $nom]);

            if ($nom === self::CAPITALE) {
                foreach (self::QUARTIERS_CAPITALE as $quartier) {
                    Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => $quartier]);
                }

                continue;
            }

            // Une ville sans aucun quartier reçoit « Centre-ville » pour que les bailleurs puissent y publier
            if (! $ville->quartiers()->exists()) {
                Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => 'Centre-ville']);
            }
        }
    }
}
