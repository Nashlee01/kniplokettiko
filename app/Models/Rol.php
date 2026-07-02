<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'rollen';

    public $timestamps = false;

    protected $fillable = [
        'naam',
    ];

    public function gebruikers(): HasMany
    {
        return $this->hasMany(Gebruiker::class, 'rol_id');
    }
}
