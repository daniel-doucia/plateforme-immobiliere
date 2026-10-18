<?php

namespace Database\Factories;

use App\Enums\Periodicite;
use App\Enums\StatutAnnonce;
use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use App\Models\Annonce;
use App\Models\Quartier;
use App\Models\User;
use App\Models\Ville;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Annonce> */
class AnnonceFactory extends Factory
{
    protected $model = Annonce::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quartier_id' => $this->quartierParDefaut(),
            'titre' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'type_bien' => fake()->randomElement(TypeBien::cases()),
            'type_offre' => TypeOffre::Location,
            'prix' => fake()->numberBetween(10, 300) * 5000,
            'periodicite' => Periodicite::Mensuel,
            'nb_pieces' => fake()->numberBetween(1, 6),
            'nb_chambres' => fake()->numberBetween(1, 4),
            'nb_salles_bain' => fake()->numberBetween(1, 2),
        ];
    }

    public function publiee(): static
    {
        return $this->state(fn () => [
            'statut' => StatutAnnonce::Publiee,
            'publiee_le' => now(),
            'expire_le' => now()->addDays(30),
        ]);
    }

    public function expiree(): static
    {
        return $this->state(fn () => [
            'statut' => StatutAnnonce::Publiee,
            'publiee_le' => now()->subDays(40),
            'expire_le' => now()->subDays(10),
        ]);
    }

    private function quartierParDefaut(): int
    {
        $ville = Ville::firstOrCreate(['nom' => "N'Djaména"]);

        return Quartier::firstOrCreate(['ville_id' => $ville->id, 'nom' => 'Moursal'])->id;
    }
}
