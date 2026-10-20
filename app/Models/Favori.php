<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favori extends Model
{
    protected $table = 'favoris';

    protected $fillable = ['user_id', 'annonce_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Annonces supprimées comprises : le favori doit pouvoir afficher « plus disponible ». */
    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class)->withTrashed();
    }
}
