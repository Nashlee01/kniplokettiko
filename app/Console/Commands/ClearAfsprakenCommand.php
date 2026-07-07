<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ========================================================================
 * COMMAND: php artisan db:clear-afspraken
 * ========================================================================
 * Doel: ENKEL afspraken verwijderen = Klanten blijven intact
 *
 * Verschil met db:clear-klanten:
 * - Verwijdert NIET de klanten/adressen
 * - Verwijdert ALLEEN afspraken + junction table
 * - Gebruikt TRUNCATE (sneller dan DELETE)
 * - Reset auto-increment IDs naar 1
 *
 * Gebruik:
 * - Test afspraken empty state
 * - Cleanup tussen feature tests
 * ========================================================================
 */
class ClearAfsprakenCommand extends Command
{
    protected $signature = 'db:clear-afspraken';
    protected $description = 'Truncate afspraken en afspraak_behandeling voor testing';

    public function handle()
    {
        // ================================================================
        // CONFIRMATION: Double-check voordat we truncate
        // ================================================================
        if (!$this->confirm('Dit verwijdert ALLE afspraken. Doorgaan?')) {
            $this->info('Geannuleerd.');
            return;
        }

        // ================================================================
        // TRUNCATE (sneller dan DELETE, reset auto-increment)
        // ================================================================
        // Disable FK checks (TRUNCATE kan FK constraints negeren)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // Junction table EERST (has FKs)
        DB::table('afspraak_behandeling')->truncate();
        // Daarna afspraken
        DB::table('afspraken')->truncate();
        // Re-enable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ================================================================
        // SUCCESS: Afspraken verwijderd, klanten intact
        // ================================================================
        $this->info('✓ Alle afspraken verwijderd!');
    }
}
