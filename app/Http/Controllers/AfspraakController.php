<?php

namespace App\Http\Controllers;

use App\Models\Afspraak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AfspraakController extends Controller
{
    private function isMedewerker(): bool
    {
        return session('gebruiker_id')
            && in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']);
    }

    private function isKlant(): bool
    {
        return session('gebruiker_id') && session('gebruiker_rol') === 'Klant';
    }

    public function index()
    {
        if (!session('gebruiker_id')) {
            return redirect()->route('login')
                ->with('error', 'Je moet eerst inloggen om afspraken te bekijken.');
        }

        try {
            if ($this->isMedewerker()) {
                // ================================================================
                // MEDEWERKER: Ziet ALLE afspraken
                // ================================================================
                // Gebruikt stored procedure sp_afspraken_overzicht()
                // Deze procedure:
                // - Joined afspraken + klanten + medewerkers + behandelingen
                // - Toont alle afspraken GESORTEERD op ID DESC (NIEUWSTE EERST)
                // - Returnt lege array als geen afspraken bestaan
                $afspraken = Afspraak::getAllForOverview();
            } else {
                // ================================================================
                // KLANT: Ziet ALLEEN eigen afspraken
                // ================================================================
                // Haal eerst het klanten record op (gekoppeld aan user via gebruiker_id)
                $klant = DB::table('klanten')
                    ->where('gebruiker_id', session('gebruiker_id'))
                    ->first();

                if (!$klant) {
                    // Klant heeft geen profiel -> geen afspraken
                    $afspraken = collect();
                } else {
                    // Query afspraken VOOR deze klant (forKlantOverview scope)
                    // Ook gesorteerd op ID DESC (nieuwste eerst)
                    $afspraken = Afspraak::query()
                        ->forKlantOverview($klant->id)
                        ->get();
                }
            }

            return view('afspraken.index', compact('afspraken'));
        } catch (\Exception $e) {
            // ERROR: Technische fout -> log + toon error view met lege lijst
            Log::error('Fout bij ophalen afspraken: ' . $e->getMessage());

            return view('afspraken.index', ['afspraken' => collect()])
                ->with('error', 'Er is iets fout gegaan bij het ophalen van de afspraken.');
        }
    }

    public function create()
    {
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken toevoegen.');
        }

        // Data ophalen voor de dropdowns.
        $klanten = DB::table('klanten')->orderBy('voornaam')->get();
        $medewerkers = DB::table('medewerkers')->where('actief', 1)->orderBy('voornaam')->get();
        $behandelingen = DB::table('behandelingen')->where('actief', 1)->orderBy('naam')->get();

        return view('afspraken.create', compact('klanten', 'medewerkers', 'behandelingen'));
    }

    public function store(Request $request)
    {
        // ============================================================================
        // AUTHORIZATION CHECK: Alleen medewerkers/eigenaren/receptionisten mogen afspraken toevoegen
        // ============================================================================
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken toevoegen.');
        }

        // ============================================================================
        // SERVER-SIDE VALIDATION: Alle inputs controleren voordat we in de database schrijven
        // ============================================================================
        // Waarom: Voorkomt ongeldige data + beschermt tegen malicious input
        // Validaties:
        // - klant_id/medewerker_id/behandeling_id moeten bestaan in database (referential integrity)
        // - datum moet in toekomst liggen (after:today)
        // - eindtijd > starttijd (logische volgorde)
        // Per veld specifieke Nederlandse foutmeldingen zodat de gebruiker precies weet wat fout is
        $request->validate([
            'klant_id' => 'required|exists:klanten,id',
            'medewerker_id' => 'required|exists:medewerkers,id',
            'behandeling_id' => 'required|exists:behandelingen,id',
            'datum' => 'required|date|after:today',
            'starttijd' => 'required',
            'eindtijd' => 'required|after:starttijd',
        ], [
            'klant_id.required' => 'Selecteer een klant.',
            'klant_id.exists' => 'De geselecteerde klant bestaat niet.',
            'medewerker_id.required' => 'Selecteer een medewerker.',
            'medewerker_id.exists' => 'De geselecteerde medewerker bestaat niet.',
            'behandeling_id.required' => 'Selecteer een behandeling.',
            'behandeling_id.exists' => 'De geselecteerde behandeling bestaat niet.',
            'datum.required' => 'Selecteer een datum.',
            'datum.date' => 'Voer een geldige datum in.',
            'datum.after' => 'De afspraak moet minimaal één dag na vandaag ingepland worden.',
            'starttijd.required' => 'Vul een starttijd in.',
            'eindtijd.required' => 'Vul een eindtijd in.',
            'eindtijd.after' => 'De eindtijd moet later zijn dan de starttijd.',
        ]);

        try {
            // ================================================================
            // STAP 1: Afspraak BASISGEGEVENS opslaan in 'afspraken' tabel
            // ================================================================
            // BELANGRIJK: We slaan GEEN behandeling_id op in afspraken tabel
            // Reden: afspraken tabel heeft geen behandeling_id kolom
            //        Treatment-data gaat naar de afspraak_behandeling KOPPELTABEL (many-to-many)
            $afspraak = Afspraak::create([
                'klant_id' => $request->klant_id,
                'medewerker_id' => $request->medewerker_id,
                'datum' => $request->datum,
                'starttijd' => $request->starttijd,
                'eindtijd' => $request->eindtijd,
                'status' => Afspraak::STATUS_GEPLAND,  // Nieuw record begint altijd als 'gepland'
                'opmerking' => $request->opmerking,
            ]);

            // ================================================================
            // STAP 2: Haal volledige BEHANDELING record op (inclusief prijs)
            // ================================================================
            // We halen het volledige behandeling record op zodat we de PRIJS kunnen snapshot-en
            // Dit is belangrijk: als de behandeling prijs later verandert, slaan we toch de
            // ORIGINELE prijs op (audit trail / historische data)
            $behandeling = DB::table('behandelingen')
                ->where('id', $request->behandeling_id)
                ->first();

            if (!$behandeling) {
                return back()->withInput()
                    ->with('error', 'De geselecteerde behandeling bestaat niet.');
            }

            // ================================================================
            // STAP 3: Link Afspraak + Behandeling in KOPPELTABEL
            // ================================================================
            // afspraak_behandeling is een JUNCTION TABLE (many-to-many):
            // - Verbindt afspraken met behandelingen
            // - Slaat PRIJS op op moment van afspraak (historische gegeven)
            // - Timestamps voor audit trail (wie/wanneer bijgewerkt)
            //
            // Waarom aparte tabel? Omdat één afspraak later MEERDERE behandelingen
            // kan hebben, en vice versa (flexibiliteit)
            DB::table('afspraak_behandeling')->insert([
                'afspraak_id' => $afspraak->id,
                'behandeling_id' => $request->behandeling_id,
                'prijs' => $behandeling->prijs,  // SNAPSHOT van prijs op DIT moment
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // SUCCESS: Terug naar overzicht met bevestigingsbericht
            return redirect()->route('afspraken.index')
                ->with('success', 'Afspraak is succesvol toegevoegd.');
        } catch (\Exception $e) {
            // ================================================================
            // ERROR HANDLING: Iets ging fout
            // ================================================================
            // - Log naar Laravel logs (voor debugging/monitoring)
            // - Return naar form MET originele input (user hoeft niet opnieuw in te vullen)
            // - Toon SPECIFIEKE foutmelding (niet generiek) zodat user weet wat mis ging
            Log::error('Fout bij toevoegen afspraak: ' . $e->getMessage());

            return back()->withInput()
                ->with('error', 'De afspraak kon niet worden toegevoegd. Fout: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        if (!session('gebruiker_id')) {
            return redirect()->route('login')
                ->with('error', 'Je moet eerst inloggen.');
        }

        try {
            $afspraak = Afspraak::query()
                ->forDetail()
                ->where('afspraken.id', $id)
                ->first();

            if (!$afspraak) {
                return redirect()->route('afspraken.index')
                    ->with('error', 'Afspraak niet gevonden.');
            }

            // Security: klant mag alleen eigen afspraak bekijken.
            if ($this->isKlant() && $afspraak->klant_gebruiker_id !== session('gebruiker_id')) {
                return redirect()->route('afspraken.index')
                    ->with('error', 'Je mag deze afspraak niet bekijken.');
            }

            return view('afspraken.show', compact('afspraak'));
        } catch (\Exception $e) {
            Log::error('Fout bij bekijken afspraak: ' . $e->getMessage());

            return redirect()->route('afspraken.index')
                ->with('error', 'De afspraak kon niet worden geopend.');
        }
    }

    public function edit(int $id)
    {
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken wijzigen.');
        }

        // Afspraak ophalen inclusief gekozen behandeling.
        $afspraak = Afspraak::find($id);

        if (!$afspraak) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Afspraak niet gevonden.');
        }

        $klanten = DB::table('klanten')->orderBy('voornaam')->get();
        $medewerkers = DB::table('medewerkers')->where('actief', 1)->orderBy('voornaam')->get();
        $behandelingen = DB::table('behandelingen')->where('actief', 1)->orderBy('naam')->get();

        return view('afspraken.edit', compact('afspraak', 'klanten', 'medewerkers', 'behandelingen'));
    }

    public function update(Request $request, int $id)
    {
        // Autorisatiecheck: alleen medewerkers/eigenaren/receptionisten mogen wijzigen
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken wijzigen.');
        }

        // Server-side validatie: dezelfde checks als bij create (integriteit)
        $request->validate([
            'klant_id' => 'required|exists:klanten,id',
            'medewerker_id' => 'required|exists:medewerkers,id',
            'behandeling_id' => 'required|exists:behandelingen,id',
            'datum' => 'required|date|after:today',
            'starttijd' => 'required',
            'eindtijd' => 'required|after:starttijd',
        ], [
            'klant_id.required' => 'Selecteer een klant.',
            'klant_id.exists' => 'De geselecteerde klant bestaat niet.',
            'medewerker_id.required' => 'Selecteer een medewerker.',
            'medewerker_id.exists' => 'De geselecteerde medewerker bestaat niet.',
            'behandeling_id.required' => 'Selecteer een behandeling.',
            'behandeling_id.exists' => 'De geselecteerde behandeling bestaat niet.',
            'datum.required' => 'Selecteer een datum.',
            'datum.date' => 'Voer een geldige datum in.',
            'datum.after' => 'De afspraak moet minimaal één dag na vandaag ingepland worden.',
            'starttijd.required' => 'Vul een starttijd in.',
            'eindtijd.required' => 'Vul een eindtijd in.',
            'eindtijd.after' => 'De eindtijd moet later zijn dan de starttijd.',
        ]);

        try {
            $afspraak = Afspraak::find($id);

            if (!$afspraak) {
                return redirect()->route('afspraken.index')
                    ->with('error', 'Afspraak niet gevonden.');
            }

            // STAP 1: Update BASISGEGEVENS van afspraak (ZONDER behandeling_id)
            // Set status naar 'gewijzigd' om te tracken dat dit record is aangepast
            $afspraak->update([
                'klant_id' => $request->klant_id,
                'medewerker_id' => $request->medewerker_id,
                'datum' => $request->datum,
                'starttijd' => $request->starttijd,
                'eindtijd' => $request->eindtijd,
                'status' => Afspraak::STATUS_GEWIJZIGD,  // Mark als 'gewijzigd'
                'opmerking' => $request->opmerking,
            ]);

            // STAP 2: Haal nieuwe BEHANDELING op
            // Gebruiker kan de behandeling switchen, dus halen we het volledige record op
            $behandeling = DB::table('behandelingen')
                ->where('id', $request->behandeling_id)
                ->first();

            if (!$behandeling) {
                return back()->withInput()
                    ->with('error', 'De geselecteerde behandeling bestaat niet.');
            }

            // STAP 3: Update KOPPELTABEL met nieuwe behandeling + prijs
            // We UPDATE het bestaande record (niet opnieuw invoegen)
            // Dit bewaart de audit trail (updated_at timestamp wijzigt)
            DB::table('afspraak_behandeling')
                ->where('afspraak_id', $id)
                ->update([
                    'behandeling_id' => $request->behandeling_id,
                    'prijs' => $behandeling->prijs,  // Nieuwe prijs snapshot
                    'updated_at' => now(),  // Tracking: wanneer was laatste wijziging
                ]);

            return redirect()->route('afspraken.index')
                ->with('success', 'De afspraak is succesvol gewijzigd.');
        } catch (\Exception $e) {
            Log::error('Fout bij wijzigen afspraak: ' . $e->getMessage());

            return back()->withInput()
                ->with('error', 'De afspraak kon niet worden gewijzigd. Fout: ' . $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken verwijderen.');
        }

        try {
            $afspraak = Afspraak::find($id);

            if (!$afspraak) {
                return redirect()->route('afspraken.index')
                    ->with('error', 'Afspraak niet gevonden.');
            }

            // Unhappy scenario: afspraak in behandeling mag niet verwijderd worden.
            if (!$afspraak->magVerwijderen()) {
                return redirect()->route('afspraken.index')
                    ->with('error', 'De afspraak kan niet worden verwijderd omdat deze al in behandeling is.');
            }

            $afspraak->delete();

            return redirect()->route('afspraken.index')
                ->with('success', 'De afspraak is succesvol verwijderd.');
        } catch (\Exception $e) {
            Log::error('Fout bij verwijderen afspraak: ' . $e->getMessage());

            return redirect()->route('afspraken.index')
                ->with('error', 'De afspraak kon niet worden verwijderd.');
        }
    }
}
