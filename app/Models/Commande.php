<?php

namespace App\Models;

use App\Enums\StatutCommande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Commande extends Model
{
    /**
     * Le statut et la référence n'y figurent pas : ils sont gérés par le code métier
     * (et plus tard par la confirmation du fournisseur de paiement).
     */
    protected $fillable = ['user_id', 'annonce_id', 'forfait_id', 'libelle', 'duree_jours', 'montant'];

    protected function casts(): array
    {
        return [
            'statut' => StatutCommande::class,
            'montant' => 'integer',
            'duree_jours' => 'integer',
            'payee_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Commande $commande) {
            $commande->statut ??= StatutCommande::EnAttente;
            $commande->reference ??= 'CMD-'.Str::upper(Str::random(10));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class);
    }

    public function forfait(): BelongsTo
    {
        return $this->belongsTo(Forfait::class);
    }
}
