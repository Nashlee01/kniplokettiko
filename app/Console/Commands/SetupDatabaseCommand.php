<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * ========================================================================
 * COMMAND: php artisan db:setup
 * ========================================================================
 * Doel: Volledige database inrichten (schema + procedures + testdata)
 * ========================================================================
 */
class SetupDatabaseCommand extends Command
{
    protected $signature = 'db:setup';
    protected $description = 'Setup database with stored procedures and test data';

    public function handle(): int
    {
        $this->info('🔧 Database setup starten...');

        try {
            $this->info('📝 Schema en procedures laden...');
            $this->executeSqlFile();

            $this->info('🌱 Testdata inladen via seeder...');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class]);

            $this->info('✅ Database setup compleet!');
            $this->newLine();
            $this->info('📌 Login credentials:');
            $this->line('   Email: emma@kniplokettiko.nl');
            $this->line('   Wachtwoord: ' . env('TEST_PASSWORD', 'password'));

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Fout: ' . $e->getMessage());
            return 1;
        }
    }

    private function executeSqlFile(): void
    {
        $sqlFile = database_path('sql/kniploket_setup.sql');
        if (!File::exists($sqlFile)) {
            throw new \Exception("SQL file not found: {$sqlFile}");
        }

        $content = File::get($sqlFile);
        $stmtCount = 0;

        // Split by DELIMITER $$ to find procedures
        $parts = explode('DELIMITER $$', $content);

        foreach ($parts as $idx => $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Check if this section contains a procedure (has END $$)
            if (strpos($part, 'END $$') !== false) {
                // Extract procedure
                $endMarker = strpos($part, 'END $$') + 6;
                $procedure = trim(substr($part, 0, $endMarker));

                // Convert DELIMITER $$ syntax to regular ; for direct PHP execution
                $procedure = str_replace('END $$', 'END', $procedure);

                if (!str_ends_with($procedure, ';')) {
                    $procedure .= ';';
                }

                try {
                    DB::unprepared($procedure);
                    $this->line('✓ Procedure created');
                    $stmtCount++;
                } catch (\Exception $e) {
                    throw new \Exception("Procedure error: " . $e->getMessage());
                }
            } else {
                // Parse regular statements
                // Remove DELIMITER ; if present
                $part = str_replace('DELIMITER ;', '', $part);

                // Split by semicolon and execute each
                $statements = explode(';', $part);

                foreach ($statements as $i => $statement) {
                    $statement = trim($statement);

                    // Skip empty lines
                    if (empty($statement)) {
                        continue;
                    }

                    // Strip comment lines from the beginning of the statement
                    while (str_starts_with($statement, '--')) {
                        // Find the end of the comment line (newline)
                        $newlinePos = strpos($statement, "\n");
                        if ($newlinePos === false) {
                            // Entire statement is a comment, skip it
                            $statement = '';
                            break;
                        }
                        // Remove the comment line
                        $statement = trim(substr($statement, $newlinePos + 1));
                    }

                    // Skip if nothing left
                    if (empty($statement)) {
                        continue;
                    }

                    // Skip any remaining DELIMITER directives
                    if (str_starts_with(strtoupper($statement), 'DELIMITER')) {
                        continue;
                    }

                    $preview = strlen($statement) > 50 ? substr($statement, 0, 50) . '...' : $statement;

                    try {
                        DB::unprepared($statement);
                        $this->line('✓ ' . $preview);
                        $stmtCount++;
                    } catch (\Exception $e) {
                        throw new \Exception("SQL error: " . $e->getMessage());
                    }
                }
            }
        }

        $this->line('✓ SQL schema en procedures geladen');
    }
}
