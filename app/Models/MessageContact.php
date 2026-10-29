<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageContact extends Model
{
    protected $table = 'messages_contact';

    // lu_le n'est volontairement pas modifiable en masse
    protected $fillable = ['nom', 'email', 'telephone', 'sujet', 'message'];

    protected function casts(): array
    {
        return ['lu_le' => 'datetime'];
    }
}
