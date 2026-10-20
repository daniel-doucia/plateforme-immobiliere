<?php

namespace App\Models;

use App\Enums\TypeBien;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forfait extends Model
{
    protected $fillable = ['nom', 'type_bien', 'duree_jours', 'prix', 'actif', 'ordre'];

    protected function casts(): array
    {
        return [
            'type_bien' => TypeBien::class,
            'duree_jours' => 'integer',
            'prix' => 'integer',
            'actif' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopePour(Builder $query, TypeBien $type): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('type_bien')->orWhere('type_bien', $type->value));
    }
}
