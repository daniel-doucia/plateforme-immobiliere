<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ville extends Model
{
    protected $fillable = ['nom', 'slug'];

    protected static function booted(): void
    {
        static::saving(function (Ville $ville) {
            $ville->slug = Str::slug($ville->nom);
        });
    }

    public function quartiers(): HasMany
    {
        return $this->hasMany(Quartier::class);
    }
}
