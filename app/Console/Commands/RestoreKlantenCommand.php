<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ========================================================================
 * COMMAND: php artisan db:restore-klanten
 * ========================================================================
 * Doel: Testdata klanten/adressen/afspraken herstellen
 *
 * Waarom:
 * - Snelle manier om schone testdata in te laden
 * - Nondeterministische klant IDs gebruikten (insertGetId stores in vars)
 * - Afspraken + adressen ook meegenomen
 *
 * Uitvoering:
 * 1. Confirm vraag (voorkomen onopzettelijke verwijdering)
 * 2. Alles cleanup (beide JOINs + base tables)
 * 3. 5 test klanten invoegen (mix van gebruiker_id=null en bestaande users)
 * 4. 5 test adressen per klant
 * 5. 5 test afspraken
 * 6. 5 afspraak_behandeling junction entries
 * ========================================================================
 */
class RestoreKlantenCommand extends Command
{
    protected $signature = 'db:restore-klanten';
    protected $description = 'Herstel de testdata klanten';

    public function handle(): int
    {
        // ================================================================
        // CONFIRMATION: Gebruiker moet expliciet bevestigen
        // ================================================================
        if ($this->confirm('Weet je zeker dat je de testdata wilt herstellen?')) {
            // ================================================================
            // CLEANUP: Alles verwijderen (in juiste volgorde!)
            // ================================================================
            // Waarom deze volgorde?
            // 1. afspraak_behandeling EERST (foreign key naar afspraken + behandelingen)
            // 2. afspraken DAARNA (foreign key naar klanten)
            // 3. adressen DAARNA (foreign key naar klanten)
            // 4. klanten TENSLOTTE (geen dependencies meer)
            DB::table('afspraak_behandeling')->delete();
            DB::table('afspraken')->delete();
            DB::table('adressen')->delete();
            DB::table('klanten')->delete();

            // ================================================================
            // TESTDATA KLANTEN: 5 klanten (mix van linked + unlinked users)
            // ================================================================
            // Waarom insertGetId? Nodig voor consistent foreign keys
            // - Nieuw geinserte klant IDs opslaan in variabelen
            // - Zelfde IDs gebruiken voor adressen/afspraken links
            // - Voorkomt hardcoded IDs die mismatch kunnen hebben
            $klant1 = DB::table('klanten')->insertGetId([
                'gebruiker_id' => 4,
                'voornaam' => 'Sanne',
                'achternaam' => 'Peters',
                'email' => 'sanne@mail.nl',
                'telefoon' => '0612345678',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $klant2 = DB::table('klanten')->insertGetId([
                'gebruiker_id' => 5,
                'voornaam' => 'Mark',
                'achternaam' => 'Visser',
                'email' => 'mark@mail.nl',
                'telefoon' => '0623456789',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $klant3 = DB::table('klanten')->insertGetId([
                'gebruiker_id' => null,
                'voornaam' => 'Noah',
                'achternaam' => 'Jansen',
                'email' => 'noah@mail.nl',
                'telefoon' => '0634567890',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $klant4 = DB::table('klanten')->insertGetId([
                'gebruiker_id' => null,
                'voornaam' => 'Eva',
                'achternaam' => 'Meijer',
                'email' => 'eva@mail.nl',
                'telefoon' => '0645678901',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $klant5 = DB::table('klanten')->insertGetId([
                'gebruiker_id' => null,
                'voornaam' => 'Daan',
                'achternaam' => 'Smit',
                'email' => 'daan@mail.nl',
                'telefoon' => '0656789012',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ================================================================
            // TESTDATA ADRESSEN: Één per klant (variabele IDs gebruiken!)
            // ================================================================
            // Zorg dat adres-inserts de DYNAMISCH gemaakte klant IDs gebruiken
            // Dit is waarom we insertGetId() deden - zodat we ze hier kunnen gebruiken
            DB::table('adressen')->insert([
                ['klant_id' => $klant1, 'straatnaam' => 'Hoofdstraat', 'huisnummer' => 25, 'postcode' => '1234AB', 'plaats' => 'Utrecht', 'created_at' => now(), 'updated_at' => now()],
                ['klant_id' => $klant2, 'straatnaam' => 'Kerkstraat', 'huisnummer' => 10, 'postcode' => '2345BC', 'plaats' => 'Nieuwegein', 'created_at' => now(), 'updated_at' => now()],
                ['klant_id' => $klant3, 'straatnaam' => 'Stationsweg', 'huisnummer' => 8, 'postcode' => '3456CD', 'plaats' => 'Houten', 'created_at' => now(), 'updated_at' => now()],
                ['klant_id' => $klant4, 'straatnaam' => 'Lindelaan', 'huisnummer' => 44, 'postcode' => '4567DE', 'plaats' => 'Zeist', 'created_at' => now(), 'updated_at' => now()],
                ['klant_id' => $klant5, 'straatnaam' => 'Schoolstraat', 'huisnummer' => 19, 'postcode' => '5678EF', 'plaats' => 'Maarssen', 'created_at' => now(), 'updated_at' => now()],
            ]);

            // ================================================================
            // TESTDATA AFSPRAKEN: 5 afspraken met statusvariatie
            // ================================================================
            // - Opslaan IDs voor junction table linking
            // - Verschillende statussen: gepland, in behandeling, gewijzigd
            // - Mix van medewerkers/datums om testscenario's te dekken
            $afspraak1 = DB::table('afspraken')->insertGetId([
                'klant_id' => $klant1,
                'medewerker_id' => 2,
                'datum' => '2026-07-02',
                'starttijd' => '09:00:00',
                'eindtijd' => '09:30:00',
                'status' => 'gepland',
                'opmerking' => 'Eerste afspraak.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $afspraak2 = DB::table('afspraken')->insertGetId([
                'klant_id' => $klant2,
                'medewerker_id' => 1,
                'datum' => '2026-07-02',
                'starttijd' => '10:00:00',
                'eindtijd' => '11:30:00',
                'status' => 'in behandeling',
                'opmerking' => 'Deze afspraak mag niet verwijderd worden.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $afspraak3 = DB::table('afspraken')->insertGetId([
                'klant_id' => $klant3,
                'medewerker_id' => 3,
                'datum' => '2026-07-03',
                'starttijd' => '13:00:00',
                'eindtijd' => '13:45:00',
                'status' => 'gepland',
                'opmerking' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $afspraak4 = DB::table('afspraken')->insertGetId([
                'klant_id' => $klant4,
                'medewerker_id' => 4,
                'datum' => '2026-07-04',
                'starttijd' => '11:00:00',
                'eindtijd' => '13:00:00',
                'status' => 'gewijzigd',
                'opmerking' => 'Extensions bijwerken.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $afspraak5 = DB::table('afspraken')->insertGetId([
                'klant_id' => $klant5,
                'medewerker_id' => 5,
                'datum' => '2026-07-05',
                'starttijd' => '15:00:00',
                'eindtijd' => '15:30:00',
                'status' => 'gepland',
                'opmerking' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ================================================================
            // TESTDATA afspraak_behandeling: JUNCTION TABLE
            // ================================================================
            // Linkt afspraken aan behandelingen (many-to-many)
            // Bevat PRIJS snapshot op moment van afspraak
            // - afspraak1 → behandeling 1 (€25)
            // - afspraak2 → behandeling 2 (€75) [status: in behandeling]
            // - etc.
            DB::table('afspraak_behandeling')->insert([
                ['afspraak_id' => $afspraak1, 'behandeling_id' => 1, 'prijs' => 25.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak2, 'behandeling_id' => 2, 'prijs' => 75.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak3, 'behandeling_id' => 3, 'prijs' => 35.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak4, 'behandeling_id' => 4, 'prijs' => 150.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak5, 'behandeling_id' => 5, 'prijs' => 40.00, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // ================================================================
            // SUCCESS: Alles succesvol ingevoegd
            // ================================================================
            // Database is nu in known state met testdata
            // Klaar voor manuele testing/feature tests
            $this->info('✅ Testdata succesvol hersteld!');
            return 0;
        }

        // ================================================================
        // CANCELLED: Gebruiker koos NIET om door te gaan
        // ================================================================
        $this->info('Operatie geannuleerd.');
        return 1;
    }
}
