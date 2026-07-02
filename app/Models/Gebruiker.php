<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gebruiker extends Model
{
    use HasFactory;

    protected $table = 'gebruikers';

    protected $fillable = [
        'rol_id',
        'naam',
        'email',
        'wachtwoord',
        'actief',
    ];

    protected function casts(): array
    {
        return [
            'actief' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function klanten(): HasMany
    {
        return $this->hasMany(Klant::class, 'gebruiker_id');
    }
}
