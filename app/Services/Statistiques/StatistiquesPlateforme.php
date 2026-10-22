<?php

namespace App\Services\Statistiques;

use App\Enums\Role;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\TypeBien;
use App\Models\Annonce;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatistiquesPlateforme
{
    private const MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    private const LIBELLES = [
        'chambre' => 'Chambres',
        'appartement' => 'Appartements',
        'maison' => 'Maisons',
        'terrain' => 'Terrains',
    ];

    /** Annonces créées par mois, du plus ancien au mois en cours. */
    public function evolution(int $mois = 6): array
    {
        $serie = [];
        $debutCourant = now()->startOfMonth();

        for ($i = $mois - 1; $i >= 0; $i--) {
            $debut = $debutCourant->copy()->subMonths($i);
            $fin = $debut->copy()->addMonth();

            $serie[] = [
                'label' => self::MOIS[$debut->month - 1],
                'valeur' => Annonce::where('created_at', '>=', $debut)->where('created_at', '<', $fin)->count(),
            ];
        }

        return $serie;
    }

    /** Annonces non supprimées, par type de bien (toujours les quatre types, même à zéro). */
    public function repartition(): array
    {
        $comptes = DB::table('annonces')
            ->whereNull('deleted_at')
            ->selectRaw('type_bien, COUNT(*) AS total')
            ->groupBy('type_bien')
            ->pluck('total', 'type_bien');

        return array_map(fn (TypeBien $type) => [
            'type' => $type->value,
            'label' => self::LIBELLES[$type->value],
            'total' => (int) ($comptes[$type->value] ?? 0),
        ], TypeBien::cases());
    }

    public function comptes(): array
    {
        $debutMois = now()->startOfMonth();
        $parRole = fn (Role $role) => User::whereHas('roles', fn ($q) => $q->where('name', $role->value));

        return [
            'bailleurs' => $parRole(Role::Bailleur)->count(),
            'bailleurs_nouveaux' => $parRole(Role::Bailleur)->where('created_at', '>=', $debutMois)->count(),
            'clients' => $parRole(Role::Client)->count(),
            'clients_nouveaux' => $parRole(Role::Client)->where('created_at', '>=', $debutMois)->count(),
        ];
    }

    public function revenus(): array
    {
        $payees = Commande::where('statut', StatutCommande::Payee->value);

        return [
            'total' => (int) (clone $payees)->sum('montant'),
            'mois' => (int) (clone $payees)->where('payee_le', '>=', now()->startOfMonth())->sum('montant'),
        ];
    }

    /** Les cinq plus anciennes annonces à relire. */
    public function enAttente(): array
    {
        return Annonce::with(['user:id,name', 'quartier'])
            ->where('statut', StatutAnnonce::EnModeration)
            ->oldest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Annonce $a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'type_bien' => $a->type_bien->libelle(),
                'prix' => $a->prix,
                'bailleur' => $a->user?->name ?? '—',
                'quartier' => $a->quartier->nom,
            ])
            ->values()
            ->all();
    }

    public function derniersPaiements(): array
    {
        return Commande::with('user:id,name')
            ->where('statut', StatutCommande::Payee->value)
            ->latest('payee_le')
            ->limit(5)
            ->get()
            ->map(fn (Commande $c) => [
                'id' => $c->id,
                'bailleur' => $c->user?->name ?? '—',
                'libelle' => $c->libelle,
                'montant' => $c->montant,
                'date' => $c->payee_le?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
