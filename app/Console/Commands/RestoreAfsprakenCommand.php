<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ========================================================================
 * COMMAND: php artisan db:restore-afspraken
 * ========================================================================
 * Doel: ALLEEN afspraken herstellen (klanten blijven intact)
 *
 * Verschil met db:restore-klanten:
 * - Verwijdert NIET de klanten/adressen
 * - Verwijdert ALLEEN afspraken + junction table
 * - Haalt klant IDs DYNAMISCH uit database (non-hardcoded!)
 * - Gebruikt array indices om afspraken aan bestaande klanten te linken
 *
 * Waarom non-hardcoded IDs?
 * - Vorig probleem: klanten had IDs 1-5, maar na delete werd 6-10
 * - Nu: Query de huidge klanten, use their IDs
 * - Voorkomt mismatch + orphaned records
 * ========================================================================
 */
class RestoreAfsprakenCommand extends Command
{
    protected $signature = 'db:restore-afspraken';
    protected $description = 'Herstel de testdata afspraken (voert hetzelfde als db:restore-klanten)';

    public function handle(): int
    {
        // ================================================================
        // CONFIRMATION
        // ================================================================
        if ($this->confirm('Weet je zeker dat je de testdata afspraken wilt herstellen?')) {
            // ================================================================
            // CLEANUP: Alleen afspraken + junction table (klanten blijven!)
            // ================================================================
            DB::table('afspraak_behandeling')->delete();
            DB::table('afspraken')->delete();

            // ================================================================
            // PREREQUISITE CHECK: Klanten moeten bestaan
            // ================================================================
            // Voorkomen dat je afspraken inserts zonder klanten
            if (DB::table('klanten')->count() === 0) {
                $this->error('❌ Geen klanten gevonden! Run eerst: php artisan db:restore-klanten');
                return 1;
            }

            // ================================================================
            // DYNAMIC KLANT IDs: Query uit database (NIET hardcoded!)
            // ================================================================
            // Haal huige klant IDs op, unabhängig van wat ze zijn
            // Dit voorkomt hardcoded 1,2,3,4,5 issues
            $klantIds = DB::table('klanten')
                ->orderBy('id')
                ->pluck('id')
                ->toArray();

            // ================================================================
            // TESTDATA AFSPRAKEN: Link aan BESTAANDE klanten
            // ================================================================
            // Gebruik klantIds array indices (klantIds[0], [1], etc)
            // Met fallback ?? voor edge cases
            $afspraak1 = DB::table('afspraken')->insertGetId([
                'klant_id' => $klantIds[0] ?? 1,
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
                'klant_id' => $klantIds[1] ?? 2,
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
                'klant_id' => $klantIds[2] ?? 3,
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
                'klant_id' => $klantIds[3] ?? 4,
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
                'klant_id' => $klantIds[4] ?? 5,
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
            // Link afspraken aan behandelingen met PRIJS snapshots
            DB::table('afspraak_behandeling')->insert([
                ['afspraak_id' => $afspraak1, 'behandeling_id' => 1, 'prijs' => 25.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak2, 'behandeling_id' => 2, 'prijs' => 75.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak3, 'behandeling_id' => 3, 'prijs' => 35.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak4, 'behandeling_id' => 4, 'prijs' => 150.00, 'created_at' => now(), 'updated_at' => now()],
                ['afspraak_id' => $afspraak5, 'behandeling_id' => 5, 'prijs' => 40.00, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // ================================================================
            // SUCCESS: Afspraken hersteld (klanten intact)
            // ================================================================
            $this->info('✓ Testdata afspraken hersteld!');
            return 0;
        }

        return 0;
