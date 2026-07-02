<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('afspraken');
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

    Schema::create('afspraken', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('klant_id')->constrained('klanten');
        $table->date('datum')->nullable();
        $table->time('starttijd')->nullable();
        $table->time('eindtijd')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('afspraken');
    Schema::dropIfExists('adressen');
    Schema::dropIfExists('klanten');
    Schema::dropIfExists('gebruikers');
    Schema::dropIfExists('rollen');
});

it('verwijdert klant succesvol en klant verdwijnt uit overzicht', function () {
    $medewerkerRoleId = DB::table('rollen')->insertGetId(['naam' => 'Medewerker']);

    $medewerkerId = DB::table('gebruikers')->insertGetId([
        'rol_id' => $medewerkerRoleId,
        'naam' => 'Emma de Vries',
        'email' => 'emma@delete.nl',
        'wachtwoord' => 'secret',
        'actief' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $klantId = DB::table('klanten')->insertGetId([
        'gebruiker_id' => null,
        'voornaam' => 'Noah',
        'achternaam' => 'Jansen',
        'email' => 'noah@test.nl',
        'telefoon' => '0634567890',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adressen')->insert([
        'klant_id' => $klantId,
        'straatnaam' => 'Stationsweg',
        'huisnummer' => 8,
        'postcode' => '3456CD',
        'plaats' => 'Houten',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->delete(route('klanten.destroy', $klantId));

    $response->assertRedirect(route('klanten.index'));
    $response->assertSessionHas('success', 'Klant succesvol verwijderd.');

    $this->assertDatabaseMissing('klanten', ['id' => $klantId]);
    $this->assertDatabaseMissing('adressen', ['klant_id' => $klantId]);

    $overviewResponse = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->get(route('klanten.index'));

    $overviewResponse->assertOk();
    $overviewResponse->assertDontSee('Noah Jansen');
});

it('blokkeert verwijderen wanneer klant nog afspraken heeft', function () {
    $medewerkerRoleId = DB::table('rollen')->insertGetId(['naam' => 'Medewerker']);

    $medewerkerId = DB::table('gebruikers')->insertGetId([
        'rol_id' => $medewerkerRoleId,
        'naam' => 'Emma de Vries',
        'email' => 'emma@afspraak.nl',
        'wachtwoord' => 'secret',
        'actief' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $klantId = DB::table('klanten')->insertGetId([
        'gebruiker_id' => null,
        'voornaam' => 'Eva',
        'achternaam' => 'Meijer',
        'email' => 'eva@test.nl',
        'telefoon' => '0645678901',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adressen')->insert([
        'klant_id' => $klantId,
        'straatnaam' => 'Lindelaan',
        'huisnummer' => 44,
        'postcode' => '4567DE',
        'plaats' => 'Zeist',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('afspraken')->insert([
        'klant_id' => $klantId,
        'datum' => '2026-07-03',
        'starttijd' => '10:00:00',
        'eindtijd' => '10:30:00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->delete(route('klanten.destroy', $klantId));

    $response->assertRedirect(route('klanten.index'));
    $response->assertSessionHas('error', 'Deze klant kan niet worden verwijderd omdat er nog afspraken aan gekoppeld zijn');

    $this->assertDatabaseHas('klanten', ['id' => $klantId]);
});
