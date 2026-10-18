<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Moderation extends Model
{
    protected $table = 'moderations';

    protected $fillable = ['annonce_id', 'moderateur_id', 'decision', 'motif'];

    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class);
    }

    public function moderateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderateur_id');
    }
}
