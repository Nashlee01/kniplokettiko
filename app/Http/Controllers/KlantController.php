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

class KlantController extends Controller
{
    private const ALLOWED_ROLES = ['Eigenaar', 'Medewerker'];

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

    public function update(Request $request, Klant $klant): RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        $validator = Validator::make(
            $request->all(),
            $this->validationRules($klant->id),
            $this->validationMessages()
        );

        if ($validator->fails()) {
            return redirect()->route('klanten.edit', $klant)
                ->withErrors($validator)
                ->withInput();
        }

        try {
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

    public function destroy(Klant $klant): RedirectResponse
    {
        $auth = $this->getAuthorizedUser();

        if (!$auth['allowed']) {
            return redirect()->route('home')->with('error', $auth['message']);
        }

        $afsprakenCount = DB::table('afspraken')
            ->where('klant_id', $klant->id)
            ->count();

        if ($afsprakenCount > 0) {
            return redirect()->route('klanten.index')
                ->with('error', 'Deze klant kan niet worden verwijderd omdat er nog afspraken aan gekoppeld zijn');
        }

        try {
            DB::transaction(function () use ($klant): void {
                $klant->delete();
            });

            Log::info('Klant succesvol verwijderd.', [
                'actor_gebruiker_id' => session('gebruiker_id'),
                'klant_id' => $klant->id,
            ]);
        } catch (\Throwable $exception) {
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

    private function loadKlantOverview(string $search, int $perPage): LengthAwarePaginator
    {
        // Gebruik de stored procedure als die beschikbaar is en er geen zoekterm is.
        if ($search === '' && DB::getDriverName() === 'mysql') {
            try {
                $rows = collect(DB::select('CALL sp_klanten_overzicht()'));

                return $this->paginateCollection($rows, $perPage);
            } catch (\Throwable $exception) {
                Log::warning('Stored procedure sp_klanten_overzicht() niet beschikbaar, fallback naar JOIN query.', [
                    'fout' => $exception->getMessage(),
                ]);
            }
        }

        return Klant::query()
            ->forOverview($search)
            ->orderBy('klanten.id')
            ->paginate($perPage)
            ->withQueryString();
    }

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

    private function validationRules(?int $ignoreKlantId = null): array
    {
        $emailRule = Rule::unique('klanten', 'email');

        if ($ignoreKlantId !== null) {
            $emailRule = $emailRule->ignore($ignoreKlantId);
        }

        return [
            'voornaam' => ['required', 'string', 'max:50'],
            'achternaam' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', $emailRule],
            'telefoon' => ['required', 'string', 'max:20'],
            'straatnaam' => ['required', 'string', 'max:50'],
            'huisnummer' => ['required', 'integer', 'min:1'],
            'postcode' => ['required', 'string', 'max:10'],
            'plaats' => ['required', 'string', 'max:50'],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'voornaam.required' => 'Vul alle verplichte velden in',
            'achternaam.required' => 'Vul alle verplichte velden in',
            'email.required' => 'Vul alle verplichte velden in',
            'email.email' => 'Voer een geldig e-mailadres in',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'telefoon.required' => 'Vul alle verplichte velden in',
            'straatnaam.required' => 'Vul alle verplichte velden in',
            'huisnummer.required' => 'Vul alle verplichte velden in',
            'postcode.required' => 'Vul alle verplichte velden in',
            'plaats.required' => 'Vul alle verplichte velden in',
        ];
    }
}
