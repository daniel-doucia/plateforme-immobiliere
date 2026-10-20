<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForfaitModification extends Model
{
    protected $table = 'forfait_modifications';

    protected $fillable = ['forfait_id', 'forfait_nom', 'user_id', 'avant', 'apres'];

    protected function casts(): array
    {
        return [
            'avant' => 'array',
            'apres' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
