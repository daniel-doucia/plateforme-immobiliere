<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AnnoncePhoto extends Model
{
    protected $table = 'annonce_photos';

    protected $fillable = ['annonce_id', 'chemin', 'mime', 'taille', 'largeur', 'hauteur', 'ordre'];

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
            'largeur' => 'integer',
            'hauteur' => 'integer',
            'ordre' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Supprimer une photo supprime aussi son fichier
        static::deleted(fn (AnnoncePhoto $photo) => Storage::disk('local')->delete($photo->chemin));
    }

    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class);
    }

    /** Adresse relative : elle fonctionne quel que soit le nom de domaine. */
    public function url(): string
    {
        return route('photos.show', $this, false);
    }
}
