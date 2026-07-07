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

    // ================================================================
    // STATUS CONSTANTS: Alle mogelijke toestanden van een afspraak
    // ================================================================
    // gepland      → Net aangemaakt, nog niet gestart
    // gewijzigd    → Aangepast door medewerker
    // in behandeling → Momenteel bezig
    // geannuleerd  → Verwijderd/afgelast
    // voltooid     → Afgerond
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
        'datum',
        'starttijd',
        'eindtijd',
        'status',
        'opmerking',
        // OPMERKING: 'behandeling_id' is NIET in deze lijst!
        // Reden: behandeling hoort NIET in de afspraken tabel
        //        het gaat naar de koppeltabel 'afspraak_behandeling'
        //        Dit is een many-to-many relatie (één afspraak kan meerdere behandelingen hebben)
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
        // ================================================================
        // CRITICAL: Junction Table JOINs
        // ================================================================
        // afspraken.behandeling_id bestaat NIET!
        // We joinen via afspraak_behandeling koppeltabel:
        // afspraken → afspraak_behandeling → behandelingen
        return $query
            ->join('klanten', 'afspraken.klant_id', '=', 'klanten.id')
            ->join('medewerkers', 'afspraken.medewerker_id', '=', 'medewerkers.id')
            ->join('afspraak_behandeling', 'afspraken.id', '=', 'afspraak_behandeling.afspraak_id')
            ->join('behandelingen', 'afspraak_behandeling.behandeling_id', '=', 'behandelingen.id')
            ->select(
                'afspraken.*',
                DB::raw("CONCAT(klanten.voornaam, ' ', klanten.achternaam) as klant"),
                DB::raw("CONCAT(medewerkers.voornaam, ' ', medewerkers.achternaam) as medewerker"),
                'behandelingen.naam as behandeling',
                'afspraak_behandeling.prijs as prijs'  // Prijs uit junction table (audit trail)
            )
            ->where('afspraken.klant_id', $klantId)
            ->orderBy('afspraken.id', 'desc');  // DESC = nieuwste eerst
    }

    // Scope voor detailweergave: uitgebreid record voor show-pagina en autorisatiecheck.
    public function scopeForDetail(Builder $query): Builder
    {
        // ================================================================
        // CRITICAL: Junction Table JOINs (zelfde patroon als scopeForKlantOverview)
        // ================================================================
        return $query
            ->join('klanten', 'afspraken.klant_id', '=', 'klanten.id')
            ->join('medewerkers', 'afspraken.medewerker_id', '=', 'medewerkers.id')
            ->join('afspraak_behandeling', 'afspraken.id', '=', 'afspraak_behandeling.afspraak_id')
            ->join('behandelingen', 'afspraak_behandeling.behandeling_id', '=', 'behandelingen.id')
            ->select(
                'afspraken.*',
                'klanten.gebruiker_id as klant_gebruiker_id',
                DB::raw("CONCAT(klanten.voornaam, ' ', klanten.achternaam) as klant"),
                DB::raw("CONCAT(medewerkers.voornaam, ' ', medewerkers.achternaam) as medewerker"),
                'behandelingen.naam as behandeling',
                'behandelingen.duur',
                'afspraak_behandeling.prijs as prijs'  // Prijs uit junction table
            );
    }

    public function magVerwijderen(): bool
    {
        // ================================================================
        // AUTORISATIE: Kan deze afspraak verwijderd worden?
        // ================================================================
        // JA: Status is anders dan 'in behandeling' (alleen toekomstige/aangepaste)
        // NEE: Status is 'in behandeling' (kan niet verwijderen als bezig!)
        return $this->status !== self::STATUS_IN_BEHANDELING;
    }

    public static function getAllForOverview()
    {
        // ================================================================
        // STATIC METHOD: Alle afspraken voor medewerker overzicht
        // ================================================================
        // Roept stored procedure sp_afspraken_overzicht() aan
        // Deze procedure:
        // - JOINs afspraken + klanten + medewerkers + behandelingen
        // - Returned ALLES gesorteerd op ID DESC (nieuwste eerst)
        // - Returnt lege collection als geen afspraken
        try {
            $results = DB::select('CALL sp_afspraken_overzicht()');
            return collect($results);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Fout bij ophalen afspraken: ' . $e->getMessage());
            return collect();  // Return empty collection bij fout
        }
    }
}
