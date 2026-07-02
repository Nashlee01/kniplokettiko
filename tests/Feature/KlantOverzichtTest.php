<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bouwt per test een minimale testdatabase op voor klantoverzicht.
beforeEach(function (): void {
    Schema::dropIfExists('adressen');
    Schema::dropIfExists('klanten');
    Schema::dropIfExists('gebruikers');
    Schema::dropIfExists('rollen');

    Schema::create('rollen', function (Blueprint $table): void {
        $table->id();
        $table->string('naam', 50)->unique();
    });

    Schema::create('gebruikers', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('rol_id')->constrained('rollen');
        $table->string('naam', 100);
        $table->string('email', 100)->unique();
        $table->string('wachtwoord', 255);
        $table->boolean('actief')->default(true);
        $table->timestamps();
    });

    Schema::create('klanten', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('gebruiker_id')->nullable()->constrained('gebruikers');
        $table->string('voornaam', 50);
        $table->string('achternaam', 50);
        $table->string('email', 100)->unique();
        $table->string('telefoon', 20);
        $table->timestamps();
    });

    Schema::create('adressen', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('klant_id')->constrained('klanten')->cascadeOnDelete();
        $table->string('straatnaam', 50);
        $table->integer('huisnummer');
        $table->string('postcode', 10);
        $table->string('plaats', 50);
        $table->timestamps();
    });
});

// Ruimt de testtabellen na elke test op.
afterEach(function (): void {
    Schema::dropIfExists('adressen');
    Schema::dropIfExists('klanten');
    Schema::dropIfExists('gebruikers');
    Schema::dropIfExists('rollen');
});

// Scenario: medewerker ziet alle geregistreerde klanten in het overzicht.
it('toont alle geregistreerde klanten in het overzicht voor een medewerker', function () {
    $medewerkerRoleId = DB::table('rollen')->insertGetId(['naam' => 'Medewerker']);

    $medewerkerId = DB::table('gebruikers')->insertGetId([
        'rol_id' => $medewerkerRoleId,
        'naam' => 'Emma de Vries',
        'email' => 'emma@test.nl',
        'wachtwoord' => 'secret',
        'actief' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $klant1 = DB::table('klanten')->insertGetId([
        'gebruiker_id' => null,
        'voornaam' => 'Sanne',
        'achternaam' => 'Peters',
        'email' => 'sanne@test.nl',
        'telefoon' => '0612345678',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adressen')->insert([
        'klant_id' => $klant1,
        'straatnaam' => 'Hoofdstraat',
        'huisnummer' => 25,
        'postcode' => '1234AB',
        'plaats' => 'Utrecht',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $klant2 = DB::table('klanten')->insertGetId([
        'gebruiker_id' => null,
        'voornaam' => 'Mark',
        'achternaam' => 'Visser',
        'email' => 'mark@test.nl',
        'telefoon' => '0623456789',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adressen')->insert([
        'klant_id' => $klant2,
        'straatnaam' => 'Kerkstraat',
        'huisnummer' => 10,
        'postcode' => '2345BC',
        'plaats' => 'Nieuwegein',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->get(route('klanten.index'));

    $response->assertOk();
    $response->assertSee('Sanne Peters');
    $response->assertSee('Mark Visser');
});

// Scenario: wanneer er geen klanten zijn, wordt een duidelijke melding getoond.
it('toont melding wanneer er geen klanten geregistreerd zijn', function () {
    $medewerkerRoleId = DB::table('rollen')->insertGetId(['naam' => 'Medewerker']);

    $medewerkerId = DB::table('gebruikers')->insertGetId([
        'rol_id' => $medewerkerRoleId,
        'naam' => 'Emma de Vries',
        'email' => 'emma@none.nl',
        'wachtwoord' => 'secret',
        'actief' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->get(route('klanten.index'));

    $response->assertOk();
    $response->assertSee('Er zijn geen klanten gevonden');
});
