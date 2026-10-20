<?php

namespace App\Models;

use App\Enums\Periodicite;
use App\Enums\StatutAnnonce;
use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Annonce extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_PHOTOS = 8;

    /**
     * Le statut, le slug et les dates de publication n'y figurent volontairement pas :
     * ils ne changent que par le code métier, jamais par un formulaire.
     */
    protected $fillable = [
        'quartier_id',
        'titre',
        'description',
        'type_bien',
        'type_offre',
        'prix',
        'periodicite',
        'caution',
        'superficie',
        'nb_pieces',
        'nb_chambres',
        'nb_salles_bain',
        'latitude',
        'longitude',
        'adresse_indicative',
    ];

    protected function casts(): array
    {
        return [
            'type_bien' => TypeBien::class,
            'type_offre' => TypeOffre::class,
            'periodicite' => Periodicite::class,
            'statut' => StatutAnnonce::class,
            'prix' => 'integer',
            'caution' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'publiee_le' => 'datetime',
            'expire_le' => 'datetime',
            'rappel_expiration_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Annonce $annonce) {
            $annonce->statut ??= StatutAnnonce::Brouillon;

            if (blank($annonce->slug)) {
                $annonce->slug = Str::slug(Str::limit($annonce->titre, 60, '')).'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AnnoncePhoto::class)->orderBy('ordre')->orderBy('id');
    }

    /** Annonces visibles par le public : publiées et non expirées. */
    public function scopePubliees(Builder $query): Builder
    {
        return $query->where('statut', StatutAnnonce::Publiee)
            ->where(fn (Builder $q) => $q->whereNull('expire_le')->orWhere('expire_le', '>', now()));
    }

    /** Même règle que scopePubliees, pour une annonce déjà chargée. */
    public function estPubliee(): bool
    {
        return ! $this->trashed()
            && $this->statut === StatutAnnonce::Publiee
            && ($this->expire_le === null || $this->expire_le->isFuture());
    }

    /** Filtres de recherche (valeurs déjà validées), partagés avec les alertes. */
    public function scopeFiltrer(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['type_offre'] ?? null, fn ($q, $v) => $q->where('type_offre', $v))
            ->when($f['type_bien'] ?? null, fn ($q, $v) => $q->where('type_bien', $v))
            ->when($f['quartier_id'] ?? null, fn ($q, $v) => $q->where('quartier_id', $v))
            ->when($f['ville_id'] ?? null, fn ($q, $v) => $q->whereHas('quartier', fn ($w) => $w->where('ville_id', $v)))
            ->when($f['prix_min'] ?? null, fn ($q, $v) => $q->where('prix', '>=', $v))
            ->when($f['prix_max'] ?? null, fn ($q, $v) => $q->where('prix', '<=', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('titre', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%")
            ));
    }

    public function transitionner(StatutAnnonce $vers): void
    {
        if (! $this->statut->peutPasserA($vers)) {
            throw new \DomainException(
                "Transition interdite : {$this->statut->value} vers {$vers->value}."
            );
        }

        $this->forceFill(['statut' => $vers]);

        if ($vers === StatutAnnonce::Publiee) {
            $this->forceFill(['publiee_le' => now()]);
        }

        $this->save();
    }
}
