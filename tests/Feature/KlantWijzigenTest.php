<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bouwt per test een minimale testdatabase op voor klant wijzigen.
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

// Scenario: medewerker wijzigt klantgegevens succesvol.
it('wijzigt klantgegevens succesvol en toont bijgewerkte gegevens', function () {
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

    $klantId = DB::table('klanten')->insertGetId([
        'gebruiker_id' => null,
        'voornaam' => 'Sanne',
        'achternaam' => 'Peters',
        'email' => 'sanne@test.nl',
        'telefoon' => '0612345678',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adressen')->insert([
        'klant_id' => $klantId,
        'straatnaam' => 'Hoofdstraat',
        'huisnummer' => 25,
        'postcode' => '1234AB',
        'plaats' => 'Utrecht',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->put(route('klanten.update', $klantId), [
            'voornaam' => 'Sanne-Lynn',
            'achternaam' => 'Peters',
            'email' => 'sanne.lynn@test.nl',
            'telefoon' => '0699999999',
            'straatnaam' => 'Nieuwe Straat',
            'huisnummer' => 99,
            'postcode' => '3456CD',
            'plaats' => 'Houten',
        ]);

    $response->assertRedirect(route('klanten.index'));
    $response->assertSessionHas('success', 'Klant succesvol gewijzigd.');

    $this->assertDatabaseHas('klanten', [
        'id' => $klantId,
        'voornaam' => 'Sanne-Lynn',
        'email' => 'sanne.lynn@test.nl',
        'telefoon' => '0699999999',
    ]);

    $this->assertDatabaseHas('adressen', [
        'klant_id' => $klantId,
        'straatnaam' => 'Nieuwe Straat',
        'huisnummer' => 99,
        'postcode' => '3456CD',
        'plaats' => 'Houten',
    ]);

    $overviewResponse = $this
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->get(route('klanten.index'));

    $overviewResponse->assertOk();
    $overviewResponse->assertSee('Sanne-Lynn Peters');
    $overviewResponse->assertSee('sanne.lynn@test.nl');
});

// Scenario: ongeldig e-mailadres blokkeert update en toont foutmelding.
it('toont melding bij ongeldig e-mailadres en slaat wijzigingen niet op', function () {
    $medewerkerRoleId = DB::table('rollen')->insertGetId(['naam' => 'Medewerker']);

    $medewerkerId = DB::table('gebruikers')->insertGetId([
        'rol_id' => $medewerkerRoleId,
        'naam' => 'Emma de Vries',
        'email' => 'emma@invalid.nl',
        'wachtwoord' => 'secret',
        'actief' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $klantId = DB::table('klanten')->insertGetId([
        'gebruiker_id' => null,
        'voornaam' => 'Mark',
        'achternaam' => 'Visser',
        'email' => 'mark@test.nl',
        'telefoon' => '0623456789',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adressen')->insert([
        'klant_id' => $klantId,
        'straatnaam' => 'Kerkstraat',
        'huisnummer' => 10,
        'postcode' => '2345BC',
        'plaats' => 'Nieuwegein',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->from(route('klanten.edit', $klantId))
        ->withSession([
            'gebruiker_id' => $medewerkerId,
            'gebruiker_naam' => 'Emma de Vries',
            'gebruiker_rol' => 'Medewerker',
        ])
        ->put(route('klanten.update', $klantId), [
            'voornaam' => 'Mark',
            'achternaam' => 'Visser',
            'email' => 'geen-geldig-email',
            'telefoon' => '0623456789',
            'straatnaam' => 'Kerkstraat',
            'huisnummer' => 10,
            'postcode' => '2345BC',
            'plaats' => 'Nieuwegein',
        ]);

    $response->assertRedirect(route('klanten.edit', $klantId));
    $response->assertSessionHasErrors('email');
    $response->assertSessionHasInput('email', 'geen-geldig-email');

    $errors = session('errors');
    expect($errors->first('email'))->toBe('Voer een geldig e-mailadres in');

    $this->assertDatabaseHas('klanten', [
        'id' => $klantId,
        'email' => 'mark@test.nl',
    ]);
});
