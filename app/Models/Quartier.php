<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Quartier extends Model
{
    protected $table = 'quartiers';

    protected $fillable = ['ville_id', 'nom', 'slug', 'arrondissement'];

    protected function casts(): array
    {
        return ['arrondissement' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Quartier $quartier) {
            $quartier->slug = Str::slug($quartier->nom);
        });
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }
}
