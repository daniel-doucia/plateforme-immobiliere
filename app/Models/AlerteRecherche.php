<?php

namespace App\Models;

use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlerteRecherche extends Model
{
    protected $table = 'alertes_recherche';

    protected $fillable = [
        'user_id',
        'q',
        'type_offre',
        'type_bien',
        'ville_id',
        'quartier_id',
        'prix_min',
        'prix_max',
        'actif',
        'derniere_verification_le',
    ];

    protected function casts(): array
    {
        return [
            'type_offre' => TypeOffre::class,
            'type_bien' => TypeBien::class,
            'prix_min' => 'integer',
            'prix_max' => 'integer',
            'actif' => 'boolean',
            'derniere_verification_le' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    /** Critères non vides, au format des filtres de la recherche publique. */
    public function parametres(): array
    {
        return array_filter([
            'q' => $this->q,
            'type_offre' => $this->type_offre?->value,
            'type_bien' => $this->type_bien?->value,
            'ville_id' => $this->ville_id,
            'quartier_id' => $this->quartier_id,
            'prix_min' => $this->prix_min,
            'prix_max' => $this->prix_max,
        ], fn ($valeur) => $valeur !== null && $valeur !== '');
    }

    public function resume(): string
    {
        $morceaux = [];

        if ($this->type_offre) {
            $morceaux[] = $this->type_offre->libelle();
        }

        if ($this->type_bien) {
            $morceaux[] = $this->type_bien->libelle();
        }

        if ($this->quartier) {
            $morceaux[] = $this->quartier->nom.', '.$this->quartier->ville->nom;
        } elseif ($this->ville) {
            $morceaux[] = $this->ville->nom;
        }

        $formater = fn (int $n) => number_format($n, 0, ',', ' ');

        if ($this->prix_min !== null && $this->prix_max !== null) {
            $morceaux[] = $formater($this->prix_min).' à '.$formater($this->prix_max).' FCFA';
        } elseif ($this->prix_min !== null) {
            $morceaux[] = 'à partir de '.$formater($this->prix_min).' FCFA';
        } elseif ($this->prix_max !== null) {
            $morceaux[] = "jusqu'à ".$formater($this->prix_max).' FCFA';
        }

        if ($this->q) {
            $morceaux[] = '« '.$this->q.' »';
        }

        return implode(' · ', $morceaux);
    }
}
