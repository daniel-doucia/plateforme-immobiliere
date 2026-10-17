<?php

namespace App\Models;

use App\Enums\StatutVerification;
use App\Enums\TypeBailleur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BailleurProfil extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'nom_commercial',
        'piece_identite_path',
        'statut_verification',
        'motif_rejet',
        'verifie_par',
        'verifie_le',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeBailleur::class,
            'statut_verification' => StatutVerification::class,
            'verifie_le' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estVerifie(): bool
    {
        return $this->statut_verification === StatutVerification::Verifie;
    }
}
