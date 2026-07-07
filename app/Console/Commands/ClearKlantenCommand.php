<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ========================================================================
 * COMMAND: php artisan db:clear-klanten
 * ========================================================================
 * Doel: Alles verwijderen = LEGE database voor empty-state testing
 *
 * Gebruiksscenario:
 * - Test "Er zijn geen klanten gevonden" message
 * - Test "Er zijn momenteel geen afspraken beschikbaar" message
 * - Cleanup tussen test runs
 *
 * Cascade delete volgorde (CRITICAL!):
 * 1. afspraak_behandeling (EERST - heeft FKs)
 * 2. afspraken
 * 3. adressen
 * 4. klanten (TENSLOTTE - heeft geen FKs meer)
 * ========================================================================
 */
class ClearKlantenCommand extends Command
{
    protected $signature = 'db:clear-klanten';
    protected $description = 'Verwijder alle klanten voor testing van unhappy scenario';

    public function handle(): int
    {
        // ================================================================
        // CONFIRMATION: Double-check voordat we alles verwijderen
        // ================================================================
        if ($this->confirm('Weet je zeker dat je ALLE klanten wilt verwijderen?')) {
            // ================================================================
            // CASCADE DELETE in juiste volgorde (foreign key constraints!)
            // ================================================================
            DB::table('afspraak_behandeling')->delete();
            DB::table('afspraken')->delete();
            DB::table('adressen')->delete();
            DB::table('klanten')->delete();

            // ================================================================
            // SUCCESS: Database is nu compleet leeg
            // ================================================================
            $this->info('✅ Alle klanten, adressen en afspraken zijn verwijderd!');
            return 0;
        }

        $this->info('Operatie geannuleerd.');
        return 1;
    }
}
