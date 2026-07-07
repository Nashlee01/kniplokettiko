<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * ================================================================
     * TESTDATA: Rollen, Gebruikers, Klanten, Medewerkers, etc.
     * ================================================================
     * - Haal TEST_PASSWORD uit .env (geen hardcoded wachtwoorden!)
     * - Bcrypt hashen met BCRYPT_ROUNDS configuratie
     * - Database transactie (atomair, rollback bij error)
     * - Logging van insertions
     *
     * Data order (foreign keys):
     * 1. rollen (basis)
     * 2. gebruikers (uses rol_id)
     * 3. klanten (nullable gebruiker_id)
     * 4. adressen (uses klant_id)
     * 5. medewerkers (nullable gebruiker_id)
     * 6. behandelingen (standalone)
     * 7. afspraken (uses klant_id, medewerker_id)
     * 8. afspraak_behandeling (junction: uses afspraak_id, behandeling_id)
     */
    public function run(): void
    {
        $password = (string) env('TEST_PASSWORD', 'password');
        $hashedPassword = Hash::make($password);

        DB::transaction(function () use ($hashedPassword): void {
            // ================================================================
            // 1. ROLLEN
            // ================================================================
            DB::table('rollen')->insertOrIgnore([
                ['naam' => 'Eigenaar'],
                ['naam' => 'Medewerker'],
                ['naam' => 'Klant'],
                ['naam' => 'Receptionist'],
                ['naam' => 'Admin'],
            ]);

            // ================================================================
            // 2. GEBRUIKERS (wachtwoord uit .env, gehasht met Bcrypt)
            // ================================================================
            $rolOwnerId = DB::table('rollen')->where('naam', 'Eigenaar')->value('id');
            $rolStaffId = DB::table('rollen')->where('naam', 'Medewerker')->value('id');
            $rolCustomerId = DB::table('rollen')->where('naam', 'Klant')->value('id');

            DB::table('gebruikers')->insertOrIgnore([
                [
                    'rol_id' => $rolOwnerId,
                    'naam' => 'Lisa Jansen',
                    'email' => 'lisa@kniplokettiko.nl',
                    'wachtwoord' => $hashedPassword,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'rol_id' => $rolStaffId,
                    'naam' => 'Emma de Vries',
                    'email' => 'emma@kniplokettiko.nl',
                    'wachtwoord' => $hashedPassword,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'rol_id' => $rolStaffId,
                    'naam' => 'Lars Bakker',
                    'email' => 'lars@kniplokettiko.nl',
                    'wachtwoord' => $hashedPassword,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'rol_id' => $rolCustomerId,
                    'naam' => 'Sanne Peters',
                    'email' => 'sanne@mail.nl',
                    'wachtwoord' => $hashedPassword,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'rol_id' => $rolCustomerId,
                    'naam' => 'Mark Visser',
                    'email' => 'mark@mail.nl',
                    'wachtwoord' => $hashedPassword,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // ================================================================
            // 3. KLANTEN (gekoppeld aan gebruikers 4 en 5)
            // ================================================================
            $sanneUser = DB::table('gebruikers')->where('email', 'sanne@mail.nl')->value('id');
            $markUser = DB::table('gebruikers')->where('email', 'mark@mail.nl')->value('id');

            $klanten = DB::table('klanten')->insertOrIgnore([
                [
                    'gebruiker_id' => $sanneUser,
                    'voornaam' => 'Sanne',
                    'achternaam' => 'Peters',
                    'email' => 'sanne@mail.nl',
                    'telefoon' => '0612345678',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => $markUser,
                    'voornaam' => 'Mark',
                    'achternaam' => 'Visser',
                    'email' => 'mark@mail.nl',
                    'telefoon' => '0623456789',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => null,
                    'voornaam' => 'Noah',
                    'achternaam' => 'Jansen',
                    'email' => 'noah@mail.nl',
                    'telefoon' => '0634567890',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => null,
                    'voornaam' => 'Eva',
                    'achternaam' => 'Meijer',
                    'email' => 'eva@mail.nl',
                    'telefoon' => '0645678901',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => null,
                    'voornaam' => 'Daan',
                    'achternaam' => 'Smit',
                    'email' => 'daan@mail.nl',
                    'telefoon' => '0656789012',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // ================================================================
            // 4. ADRESSEN (gekoppeld aan klanten)
            // ================================================================
            $klantIds = DB::table('klanten')->pluck('id')->toArray();

            if (count($klantIds) >= 5) {
                DB::table('adressen')->insertOrIgnore([
                    [
                        'klant_id' => $klantIds[0],
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 25,
                        'postcode' => '1234AB',
                        'plaats' => 'Utrecht',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[1],
                        'straatnaam' => 'Kerkstraat',
                        'huisnummer' => 10,
                        'postcode' => '2345BC',
                        'plaats' => 'Nieuwegein',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[2],
                        'straatnaam' => 'Stationsweg',
                        'huisnummer' => 8,
                        'postcode' => '3456CD',
                        'plaats' => 'Houten',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[3],
                        'straatnaam' => 'Lindelaan',
                        'huisnummer' => 44,
                        'postcode' => '4567DE',
                        'plaats' => 'Zeist',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[4],
                        'straatnaam' => 'Schoolstraat',
                        'huisnummer' => 19,
                        'postcode' => '5678EF',
                        'plaats' => 'Maarssen',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }

            // ================================================================
            // 5. MEDEWERKERS (gekoppeld aan gebruikers 1-3 + 2 extra)
            // ================================================================
            $lisaUser = DB::table('gebruikers')->where('email', 'lisa@kniplokettiko.nl')->value('id');
            $emmaUser = DB::table('gebruikers')->where('email', 'emma@kniplokettiko.nl')->value('id');
            $larsUser = DB::table('gebruikers')->where('email', 'lars@kniplokettiko.nl')->value('id');

            DB::table('medewerkers')->insertOrIgnore([
                [
                    'gebruiker_id' => $lisaUser,
                    'voornaam' => 'Lisa',
                    'achternaam' => 'Jansen',
                    'email' => 'lisa@kniplokettiko.nl',
                    'telefoon' => '0611111111',
                    'functie' => 'Eigenaar',
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => $emmaUser,
                    'voornaam' => 'Emma',
                    'achternaam' => 'de Vries',
                    'email' => 'emma@kniplokettiko.nl',
                    'telefoon' => '0622222222',
                    'functie' => 'Kleurspecialist',
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => $larsUser,
                    'voornaam' => 'Lars',
                    'achternaam' => 'Bakker',
                    'email' => 'lars@kniplokettiko.nl',
                    'telefoon' => '0633333333',
                    'functie' => 'Stylist',
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => null,
                    'voornaam' => 'Mila',
                    'achternaam' => 'Bos',
                    'email' => 'mila@kniplokettiko.nl',
                    'telefoon' => '0644444444',
                    'functie' => 'Extensions specialist',
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'gebruiker_id' => null,
                    'voornaam' => 'Tim',
                    'achternaam' => 'Kok',
                    'email' => 'tim@kniplokettiko.nl',
                    'telefoon' => '0655555555',
                    'functie' => 'Kapper',
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // ================================================================
            // 6. BEHANDELINGEN
            // ================================================================
            DB::table('behandelingen')->insertOrIgnore([
                [
                    'naam' => 'Knippen',
                    'omschrijving' => 'Haar knippen en model brengen.',
                    'duur' => 30,
                    'prijs' => 25.00,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'naam' => 'Verven',
                    'omschrijving' => 'Haar kleuren inclusief advies.',
                    'duur' => 90,
                    'prijs' => 75.00,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'naam' => 'Stylen',
                    'omschrijving' => 'Haar fohnen en stylen.',
                    'duur' => 45,
                    'prijs' => 35.00,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'naam' => 'Extensions',
                    'omschrijving' => 'Extensions plaatsen of bijwerken.',
                    'duur' => 120,
                    'prijs' => 150.00,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'naam' => 'Haarverzorging',
                    'omschrijving' => 'Wasbehandeling en verzorgend masker.',
                    'duur' => 40,
                    'prijs' => 40.00,
                    'actief' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // ================================================================
            // 7. AFSPRAKEN
            // ================================================================
            $medewerkerIds = DB::table('medewerkers')->pluck('id')->toArray();

            if (count($klantIds) >= 5 && count($medewerkerIds) >= 5) {
                DB::table('afspraken')->insertOrIgnore([
                    [
                        'klant_id' => $klantIds[0],
                        'medewerker_id' => $medewerkerIds[1],
                        'datum' => '2026-07-02',
                        'starttijd' => '09:00:00',
                        'eindtijd' => '09:30:00',
                        'status' => 'gepland',
                        'opmerking' => 'Eerste afspraak.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[1],
                        'medewerker_id' => $medewerkerIds[0],
                        'datum' => '2026-07-02',
                        'starttijd' => '10:00:00',
                        'eindtijd' => '11:30:00',
                        'status' => 'in behandeling',
                        'opmerking' => 'Deze afspraak mag niet verwijderd worden.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[2],
                        'medewerker_id' => $medewerkerIds[2],
                        'datum' => '2026-07-03',
                        'starttijd' => '13:00:00',
                        'eindtijd' => '13:45:00',
                        'status' => 'gepland',
                        'opmerking' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[3],
                        'medewerker_id' => $medewerkerIds[3],
                        'datum' => '2026-07-04',
                        'starttijd' => '11:00:00',
                        'eindtijd' => '13:00:00',
                        'status' => 'gewijzigd',
                        'opmerking' => 'Extensions bijwerken.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'klant_id' => $klantIds[4],
                        'medewerker_id' => $medewerkerIds[4],
                        'datum' => '2026-07-05',
                        'starttijd' => '15:00:00',
                        'eindtijd' => '15:30:00',
                        'status' => 'gepland',
                        'opmerking' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

                // ================================================================
                // 8. AFSPRAAK_BEHANDELING (Junction table)
                // ================================================================
                $afspraakIds = DB::table('afspraken')->pluck('id')->toArray();
                $behandelingIds = DB::table('behandelingen')->pluck('id')->toArray();

                if (count($afspraakIds) >= 5 && count($behandelingIds) >= 5) {
                    DB::table('afspraak_behandeling')->insertOrIgnore([
                        [
                            'afspraak_id' => $afspraakIds[0],
                            'behandeling_id' => $behandelingIds[0],
                            'prijs' => 25.00,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'afspraak_id' => $afspraakIds[1],
                            'behandeling_id' => $behandelingIds[1],
                            'prijs' => 75.00,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'afspraak_id' => $afspraakIds[2],
                            'behandeling_id' => $behandelingIds[2],
                            'prijs' => 35.00,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'afspraak_id' => $afspraakIds[3],
                            'behandeling_id' => $behandelingIds[3],
                            'prijs' => 150.00,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'afspraak_id' => $afspraakIds[4],
                            'behandeling_id' => $behandelingIds[4],
                            'prijs' => 40.00,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);
                }
            }
        });
    }
}
