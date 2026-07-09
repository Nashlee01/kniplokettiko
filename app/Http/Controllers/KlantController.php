<?php

namespace App\Http\Controllers;

use App\Models\Klant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

// Controller voor klantbeheer: overzicht, toevoegen, wijzigen en verwijderen.
class KlantController extends Controller
{
    // Alleen deze rollen mogen klantbeheer gebruiken.
    private const ALLOWED_ROLES = ['Eigenaar', 'Medewerker'];

    // ================================================================
    // INDEX: Klantenoverzicht met zoekfilter en paginatie
    // ================================================================
    // - Check autorisatie (alleen Eigenaar/Medewerker)
    // - Haal zoekopdracht op uit query parameter ?q=
    // - Load klanten (via stored procedure OR fallback naar JOINs)
    // - Paginate resultaten (5 per pagina)
    public function index(Request $request): View|RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        $search = trim((string) $request->query('q', ''));
        $klanten = $this->loadKlantOverview($search, 5);

        return view('klanten.index', [
            'klanten' => $klanten,
            'search' => $search,
            'gebruikerNaam' => (string) session('gebruiker_naam', ''),
            'gebruikerRol' => (string) session('gebruiker_rol', ''),
        ]);
    }

    // ================================================================
    // CREATE: Toon formulier voor nieuwe klant
    // ================================================================
    public function create(): View|RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        return view('klanten.create', [
            'gebruikerNaam' => (string) session('gebruiker_naam', ''),
            'gebruikerRol' => (string) session('gebruiker_rol', ''),
        ]);
    }

    // ================================================================
    // STORE: Opslaan nieuwe klant + adres
    // ================================================================
    // - Autorisatiecheck (alleen Eigenaar/Medewerker)
    // - Server-side validatie (alle velden verplicht + specifieke regels)
    // - Database transactie (beide tabellen atomair)
    // - Logging voor audit trail
    public function store(Request $request): RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        $validator = Validator::make(
            $request->all(),
            $this->validationRules(),
            $this->validationMessages()
        );

        if ($validator->fails()) {
            return redirect()->route('klanten.create')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // ================================================================
            // TRANSACTIE: Zowel klant als adres moeten slagen
            // ================================================================
            // Als één fails, alles rollback (halve writes voorkomen)
            DB::transaction(function () use ($request): void {
                $klant = Klant::create([
                    'gebruiker_id' => null,
                    'voornaam' => trim((string) $request->input('voornaam')),
                    'achternaam' => trim((string) $request->input('achternaam')),
                    'email' => trim((string) $request->input('email')),
                    'telefoon' => trim((string) $request->input('telefoon')),
                ]);

                DB::table('adressen')->insert([
                    'klant_id' => $klant->id,
                    'straatnaam' => trim((string) $request->input('straatnaam')),
                    'huisnummer' => (int) $request->input('huisnummer'),
                    'postcode' => strtoupper(trim((string) $request->input('postcode'))),
                    'plaats' => trim((string) $request->input('plaats')),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            Log::info('Klant succesvol aangemaakt.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_email' => trim((string) $request->input('email')),
            ]);
        } catch (\Throwable $exception) {
            // ================================================================
            // ERROR: Logging voor debugging
            // ================================================================
            // Log bevat: wie, wat, en foutmelding
            Log::error('Klant opslaan mislukt.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_email' => trim((string) $request->input('email')),
                'fout' => $exception->getMessage(),
            ]);

            return redirect()->route('klanten.create')
                ->withInput()
                ->with('error', 'Er is iets misgegaan bij het opslaan van de klant.');
        }

        return redirect()->route('klanten.index')
            ->with('success', 'Klant succesvol toegevoegd.');
    }

    // ================================================================
    // EDIT: Toon bewerkformulier voor bestaande klant
    // ================================================================
    // - Autorisatiecheck
    // - Haal klant + bijbehorend adres op
    // - Toon beide in formulier
    public function edit(Klant $klant): View|RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        $adres = DB::table('adressen')
            ->where('klant_id', $klant->id)
            ->first();

        return view('klanten.edit', [
            'klant' => $klant,
            'adres' => $adres,
            'gebruikerNaam' => (string) session('gebruiker_naam', ''),
            'gebruikerRol' => (string) session('gebruiker_rol', ''),
        ]);
    }

    // ================================================================
    // UPDATE: Wijzig bestaande klant + adres
    // ================================================================
    // - Validatie (email unique check ignoreert huidge klant)
    // - Transactie (klant + adres atomair)
    // - Logging voor audit trail
    public function update(Request $request, Klant $klant): RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        $validator = Validator::make(
            $request->all(),
            $this->validationRules($klant->id),  // Pass ID om email-unique te ignoren
            $this->validationMessages()
        );

        if ($validator->fails()) {
            return redirect()->route('klanten.edit', $klant)
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // ================================================================
            // TRANSACTIE: Update klant + adres atomair
            // ================================================================
            DB::transaction(function () use ($request, $klant): void {
                $klant->update([
                    'voornaam' => trim((string) $request->input('voornaam')),
                    'achternaam' => trim((string) $request->input('achternaam')),
                    'email' => trim((string) $request->input('email')),
                    'telefoon' => trim((string) $request->input('telefoon')),
                ]);

                $adresData = [
                    'straatnaam' => trim((string) $request->input('straatnaam')),
                    'huisnummer' => (int) $request->input('huisnummer'),
                    'postcode' => strtoupper(trim((string) $request->input('postcode'))),
                    'plaats' => trim((string) $request->input('plaats')),
                    'updated_at' => now(),
                ];

                // ============================================================
                // ADRES: Bestaand UPDATE of nieuw INSERT
                // ============================================================
                // Controleer of adres al bestaat voor deze klant
                // Zo ja: update bestaande record
                // Zo nee: voeg nieuw record in
                $adresBestaat = DB::table('adressen')
                    ->where('klant_id', $klant->id)
                    ->exists();

                if ($adresBestaat) {
                    DB::table('adressen')
                        ->where('klant_id', $klant->id)
                        ->update($adresData);
                } else {
                    DB::table('adressen')->insert([
                        ...$adresData,
                        'klant_id' => $klant->id,
                        'created_at' => now(),
                    ]);
                }
            });

            Log::info('Klant succesvol bijgewerkt.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_id' => $klant->id,
            ]);
        } catch (\Throwable $exception) {
            // ================================================================
            // ERROR: Logging met context
            // ================================================================
            Log::error('Klant bijwerken mislukt.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_id' => $klant->id,
                'fout' => $exception->getMessage(),
            ]);

            return redirect()->route('klanten.edit', $klant)
                ->withInput()
                ->with('error', 'Er is iets misgegaan bij het wijzigen van de klant.');
        }

        return redirect()->route('klanten.index')
            ->with('success', 'Klant succesvol gewijzigd.');
    }

    // ================================================================
    // DESTROY: Verwijder klant + gekoppeld adres
    // ================================================================
    // - Businessregel: Kan NIET als er afspraken gekoppeld zijn
    // - Transactie (cascade delete via foreign key constraint)
    // - Logging voor audit trail
    public function destroy(Klant $klant): RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        // ================================================================
        // BUSINESSREGEL: Klanten met afspraken niet verwijderen
        // ================================================================
        // Voorkomt orphaned afspraken records
        $afsprakenCount = DB::table('afspraken')
            ->where('klant_id', $klant->id)
            ->count();

        // Businessregel: klanten met afspraken mogen niet verwijderd worden.
        if ($afsprakenCount > 0) {
            return redirect()->route('klanten.index')
                ->with('error', 'Deze klant kan niet worden verwijderd omdat er nog afspraken aan gekoppeld zijn');
        }

        try {
            // ================================================================
            // TRANSACTIE: Delete klant (adres cascade delete automatisch)
            // ================================================================
            // Foreign key constraint zorgt dat adressen ook verwijderd worden
            DB::transaction(function () use ($klant): void {
                $klant->delete();
            });

            Log::info('Klant succesvol verwijderd.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_id' => $klant->id,
            ]);
        } catch (\Throwable $exception) {
            // ================================================================
            // ERROR: Logging
            // ================================================================
            Log::error('Klant verwijderen mislukt.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_id' => $klant->id,
                'fout' => $exception->getMessage(),
            ]);

            return redirect()->route('klanten.index')
                ->with('error', 'Er is iets misgegaan bij het verwijderen van de klant.');
        }

        return redirect()->route('klanten.index')
            ->with('success', 'Klant succesvol verwijderd.');
    }

    // ================================================================
    // getAuthorizedUser(): Check sessie + rol autorisatie
    // ================================================================
    // Returns: [allowed => bool, message => string]
    // - Valideerd: gebruiker is ingelogd
    // - Valideerd: gebruiker heeft juiste rol (Eigenaar/Medewerker)
    // - Logs warnings als autorisatie denied
    private function getAuthorizedUser(): array
    {
        $gebruikerId = session('gebruiker_id');

        if (!$gebruikerId) {
            Log::warning('Toegang klantbeheer geweigerd: geen actieve sessie.');

            return [
                'allowed' => false,
                'message' => 'Log eerst in om klanten te beheren.',
            ];
        }

        $gebruiker = DB::table('gebruikers')
            ->join('rollen', 'gebruikers.rol_id', '=', 'rollen.id')
            ->select('gebruikers.id', 'rollen.naam as rol')
            ->where('gebruikers.id', $gebruikerId)
            ->where('gebruikers.actief', 1)
            ->first();

        $rolNaam = $gebruiker?->rol;

        if (!$gebruiker || !$rolNaam || !in_array($rolNaam, self::ALLOWED_ROLES, true)) {
            Log::warning('Toegang klantbeheer geweigerd: onvoldoende rechten.', [
                'gebruiker_id' => $gebruikerId,
                'rol' => $rolNaam,
            ]);

            return [
                'allowed' => false,
                'message' => 'Je hebt geen toegang tot klantbeheer.',
            ];
        }

        return ['allowed' => true];
    }

    // Levert de data voor het overzicht op, met stored procedure fallback naar JOIN-query.
    private function loadKlantOverview(string $search, int $perPage): LengthAwarePaginator
    {
        // ================================================================
        // PRIORITY 1: Gebruik STORED PROCEDURE (performance + consistency)
        // ================================================================
        // - Alleen voor MySQL (niet voor SQLite tests)
        // - Alleen als er GEEN zoekterm is (procedure handelt search niet af)
        // - Procedure gebruikt INNER JOINs (veiliger dan loose JOINs)
        if ($search === '' && DB::getDriverName() === 'mysql') {
            try {
                // sp_klanten_overzicht() uit database/sql/kniploket_setup.sql
                // Deze procedure:
                // - JOINs klanten met adressen
                // - Returned alle klanten GESORTEERD OP ID DESC (NIEUWSTE EERST)
                $rows = collect(DB::select('CALL sp_klanten_overzicht()'));

                return $this->paginateCollection($rows, $perPage);
            } catch (\Throwable $exception) {
                Log::warning('Stored procedure sp_klanten_overzicht() niet beschikbaar, fallback naar JOIN query.', [
                    'fout' => $exception->getMessage(),
                ]);
            }
        }

        // ================================================================
        // FALLBACK: Laravel Query Builder (voor tests + searches)
        // ================================================================
        // Gebruikt de Klant model scope 'forOverview' voor searches
        // Belangrijk: orderBy DESC zodat NIEUWSTE klanten EERST verschijnen
        return Klant::query()
            ->forOverview($search)
            ->orderBy('klanten.id', 'desc')  // ← DESC = NIEUWSTE EERST
            ->paginate($perPage)
            ->withQueryString();
    }

    // Handmatige paginatie voor resultaten uit een stored procedure.
    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $items->forPage($currentPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    // Centrale validatieregels voor create/update.
    private function validationRules(?int $ignoreKlantId = null): array
    {
        $emailRule = Rule::unique('klanten', 'email');

        if ($ignoreKlantId !== null) {
            $emailRule = $emailRule->ignore($ignoreKlantId);
        }

        return [
            'voornaam' => ['required', 'string', 'max:50', 'regex:/^[\pL][\pL\s\'-]*$/u'],
            'achternaam' => ['required', 'string', 'max:50', 'regex:/^[\pL][\pL\s\'-]*$/u'],
            'email' => ['required', 'email', 'max:100', $emailRule],
            'telefoon' => ['required', 'string', 'max:20'],
            'straatnaam' => ['required', 'string', 'max:50'],
            'huisnummer' => ['required', 'integer', 'min:1'],
            'postcode' => ['required', 'string', 'max:10'],
            'plaats' => ['required', 'string', 'max:50'],
        ];
    }

    // Centrale foutmeldingen in het Nederlands voor de eindgebruiker.
    private function validationMessages(): array
    {
        return [
            'voornaam.required' => 'Voornaam is verplicht.',
            'voornaam.string' => 'Voornaam moet tekst zijn.',
            'voornaam.max' => 'Voornaam mag max 50 karakters zijn.',
            'voornaam.regex' => 'Voornaam mag alleen letters, spaties, apostrof en koppelteken bevatten en moet met een letter beginnen.',
            'achternaam.required' => 'Achternaam is verplicht.',
            'achternaam.string' => 'Achternaam moet tekst zijn.',
            'achternaam.max' => 'Achternaam mag max 50 karakters zijn.',
            'achternaam.regex' => 'Achternaam mag alleen letters, spaties, apostrof en koppelteken bevatten en moet met een letter beginnen.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'email.max' => 'E-mailadres mag max 100 karakters zijn.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'telefoon.required' => 'Telefoonnummer is verplicht.',
            'telefoon.string' => 'Telefoonnummer moet tekst zijn.',
            'telefoon.max' => 'Telefoonnummer mag max 20 karakters zijn.',
            'straatnaam.required' => 'Straatnaam is verplicht.',
            'straatnaam.string' => 'Straatnaam moet tekst zijn.',
            'straatnaam.max' => 'Straatnaam mag max 50 karakters zijn.',
            'huisnummer.required' => 'Huisnummer is verplicht.',
            'huisnummer.integer' => 'Huisnummer moet een getal zijn.',
            'huisnummer.min' => 'Huisnummer moet minstens 1 zijn.',
            'postcode.required' => 'Postcode is verplicht.',
            'postcode.string' => 'Postcode moet tekst zijn.',
            'postcode.max' => 'Postcode mag max 10 karakters zijn.',
            'plaats.required' => 'Plaats is verplicht.',
            'plaats.string' => 'Plaats moet tekst zijn.',
            'plaats.max' => 'Plaats mag max 50 karakters zijn.',
        ];
    }
}
