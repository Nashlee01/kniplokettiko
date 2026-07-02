<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Afspraak extends Model
{
    use HasFactory;

    public const STATUS_GEPLAND = 'gepland';
    public const STATUS_GEWIJZIGD = 'gewijzigd';
    public const STATUS_IN_BEHANDELING = 'in behandeling';
    public const STATUS_GEANNULEERD = 'geannuleerd';
    public const STATUS_VOLTOOID = 'voltooid';

    public const STATUSES = [
        self::STATUS_GEPLAND,
        self::STATUS_GEWIJZIGD,
        self::STATUS_IN_BEHANDELING,
        self::STATUS_GEANNULEERD,
        self::STATUS_VOLTOOID,
    ];

    protected $table = 'afspraken';

    protected $fillable = [
        'klant_id',
        'medewerker_id',
        'behandeling_id',
        'datum',
        'starttijd',
        'eindtijd',
        'status',
        'opmerking',
    ];

    protected $casts = [
        'datum' => 'date',
        // MySQL TIME kolommen blijven als string (HH:MM:SS) zonder onnodige datetime-conversie.
        'starttijd' => 'string',
        'eindtijd' => 'string',
    ];

    public function klant(): BelongsTo
    {
        return $this->belongsTo(Klant::class);
    }

    // Scope voor klantenoverzicht: complete afspraakinformatie met benodigde JOINs.
    public function scopeForKlantOverview(Builder $query, int $klantId): Builder
    {
        return $query
            ->join('klanten', 'afspraken.klant_id', '=', 'klanten.id')
            ->join('medewerkers', 'afspraken.medewerker_id', '=', 'medewerkers.id')
            ->join('behandelingen', 'afspraken.behandeling_id', '=', 'behandelingen.id')
            ->select(
                'afspraken.*',
                DB::raw("CONCAT(klanten.voornaam, ' ', klanten.achternaam) as klant"),
                DB::raw("CONCAT(medewerkers.voornaam, ' ', medewerkers.achternaam) as medewerker"),
                'behandelingen.naam as behandeling',
                'behandelingen.prijs as prijs'
            )
            ->where('afspraken.klant_id', $klantId)
            ->orderBy('afspraken.datum')
            ->orderBy('afspraken.starttijd');
    }

    // Scope voor detailweergave: uitgebreid record voor show-pagina en autorisatiecheck.
    public function scopeForDetail(Builder $query): Builder
    {
        return $query
            ->join('klanten', 'afspraken.klant_id', '=', 'klanten.id')
            ->join('medewerkers', 'afspraken.medewerker_id', '=', 'medewerkers.id')
            ->join('behandelingen', 'afspraken.behandeling_id', '=', 'behandelingen.id')
            ->select(
                'afspraken.*',
                'klanten.gebruiker_id as klant_gebruiker_id',
                DB::raw("CONCAT(klanten.voornaam, ' ', klanten.achternaam) as klant"),
                DB::raw("CONCAT(medewerkers.voornaam, ' ', medewerkers.achternaam) as medewerker"),
                'behandelingen.naam as behandeling',
                'behandelingen.duur',
                'behandelingen.prijs as prijs'
            );
    }

    public function magVerwijderen(): bool
    {
        return $this->status !== self::STATUS_IN_BEHANDELING;
    }
}
