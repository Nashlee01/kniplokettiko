<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

// Model voor klanten met een scope voor het overzicht inclusief JOIN op adressen.
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

    // Scope voor overzichtspagina: koppelt klant- en adresgegevens en ondersteunt zoeken.
    public function scopeForOverview(Builder $query, string $search = ''): Builder
    {
        // ================================================================
        // INNER JOIN: Haal ALLEEN klanten met adres-informatie
        // ================================================================
        // INNER JOIN: Elke klant MOET een adres hebben (store() voegt beiden in)
        // Dit reflecteert de zakelijklogica en voorkomt NULL-waarden in templates
        // Select specifieke kolommen (niet * om bandbreedte te sparen)
        $query
            ->join('adressen', 'klanten.id', '=', 'adressen.klant_id')
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

        // ================================================================
        // SEARCH: Filter op voornaam/achternaam/email/telefoon/adres
        // ================================================================
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
