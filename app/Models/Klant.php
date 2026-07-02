<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Klant extends Model
{
    use HasFactory;

    protected $table = 'klanten';

    protected $fillable = [
        'gebruiker_id',
        'voornaam',
        'achternaam',
        'email',
        'telefoon',
    ];

    public function gebruiker(): BelongsTo
    {
        return $this->belongsTo(Gebruiker::class, 'gebruiker_id');
    }

    public function adres(): HasOne
    {
        return $this->hasOne(Adres::class, 'klant_id');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $innerQuery) use ($search): void {
            $innerQuery->where('voornaam', 'like', "%{$search}%")
                ->orWhere('achternaam', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telefoon', 'like', "%{$search}%")
                ->orWhereHas('adres', function (Builder $adresQuery) use ($search): void {
                    $adresQuery->where('straatnaam', 'like', "%{$search}%")
                        ->orWhere('plaats', 'like', "%{$search}%")
                        ->orWhere('postcode', 'like', "%{$search}%");
                });
        });
    }

    public function scopeForOverview(Builder $query, string $search = ''): Builder
    {
        $query
            ->leftJoin('adressen', 'klanten.id', '=', 'adressen.klant_id')
            ->select(
                'klanten.id',
                'klanten.voornaam',
                'klanten.achternaam',
                'klanten.email',
                'klanten.telefoon',
                'adressen.straatnaam',
                'adressen.huisnummer',
                'adressen.postcode',
                'adressen.plaats'
            );

        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $innerQuery) use ($search): void {
            $innerQuery->where('klanten.voornaam', 'like', "%{$search}%")
                ->orWhere('klanten.achternaam', 'like', "%{$search}%")
                ->orWhere('klanten.email', 'like', "%{$search}%")
                ->orWhere('klanten.telefoon', 'like', "%{$search}%")
                ->orWhere('adressen.straatnaam', 'like', "%{$search}%")
                ->orWhere('adressen.postcode', 'like', "%{$search}%")
                ->orWhere('adressen.plaats', 'like', "%{$search}%");
        });
    }
}
