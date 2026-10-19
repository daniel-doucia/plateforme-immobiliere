<?php

namespace App\Services\Statistiques;

use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\Moderation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StatistiquesAnnonces
{
    /**
     * Compteurs par bailleur, uniquement pour les identifiants demandés.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, array<string, int>> indexé par identifiant de bailleur
     */
    public function parBailleur(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if ($userIds === []) {
            return [];
        }

        $annonces = $this->annonces($userIds)->addSelect('annonces.user_id')->groupBy('annonces.user_id')->get()->keyBy('user_id');
        $paiements = $this->paiements($userIds)->addSelect('commandes.user_id')->groupBy('commandes.user_id')->get()->keyBy('user_id');
        $rejets = $this->rejets($userIds)->addSelect('annonces.user_id')->groupBy('annonces.user_id')->get()->keyBy('user_id');

        $resultat = [];
        foreach ($userIds as $id) {
            $resultat[$id] = $this->assembler($annonces->get($id), $paiements->get($id), $rejets->get($id));
        }

        return $resultat;
    }

    /** Compteurs de toute la plateforme. */
    public function globales(): array
    {
        return $this->assembler(
            $this->annonces(null)->first(),
            $this->paiements(null)->first(),
            $this->rejets(null)->first(),
        );
    }

    private function annonces(?array $ids): Builder
    {
        $requete = Annonce::query()->selectRaw('COUNT(*) AS total');

        // Les noms de colonnes viennent de l'énumération : jamais d'une saisie utilisateur
        foreach (StatutAnnonce::cases() as $statut) {
            $requete->selectRaw("SUM(CASE WHEN annonces.statut = ? THEN 1 ELSE 0 END) AS {$statut->value}", [$statut->value]);
        }

        return $ids === null ? $requete : $requete->whereIn('annonces.user_id', $ids);
    }

    private function paiements(?array $ids): Builder
    {
        $requete = Commande::query()
            ->where('commandes.statut', StatutCommande::Payee->value)
            ->selectRaw('COUNT(DISTINCT commandes.annonce_id) AS annonces_payees, COALESCE(SUM(commandes.montant), 0) AS montant_paye');

        return $ids === null ? $requete : $requete->whereIn('commandes.user_id', $ids);
    }

    private function rejets(?array $ids): Builder
    {
        $requete = Moderation::query()
            ->join('annonces', 'annonces.id', '=', 'moderations.annonce_id')
            ->where('moderations.decision', 'rejete')
            ->selectRaw('COUNT(*) AS rejets');

        return $ids === null ? $requete : $requete->whereIn('annonces.user_id', $ids);
    }

    private function assembler(?Model $annonces, ?Model $paiements, ?Model $rejets): array
    {
        $stats = ['total' => (int) ($annonces?->total ?? 0)];

        foreach (StatutAnnonce::cases() as $statut) {
            $stats[$statut->value] = (int) ($annonces?->{$statut->value} ?? 0);
        }

        $stats['annonces_payees'] = (int) ($paiements?->annonces_payees ?? 0);
        $stats['montant_paye'] = (int) ($paiements?->montant_paye ?? 0);
        $stats['rejets_historique'] = (int) ($rejets?->rejets ?? 0);

        return $stats;
    }
}
