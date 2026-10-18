<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'commande_id',
        'fournisseur',
        'reference_fournisseur',
        'montant',
        'statut',
        'payload',
        'paye_le',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'payload' => 'array',
            'paye_le' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
