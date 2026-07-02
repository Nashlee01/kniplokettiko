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
                // Stored procedure met JOINs voor alle afspraken.
                $afspraken = collect(DB::select('CALL sp_afspraken_overzicht()'));
            } else {
                // Klant ziet alleen eigen afspraken.
                $klant = DB::table('klanten')
                    ->where('gebruiker_id', session('gebruiker_id'))
                    ->first();

                if (!$klant) {
                    $afspraken = collect();
                } else {
                    $afspraken = Afspraak::query()
                        ->forKlantOverview($klant->id)
                        ->get();
                }
            }

            return view('afspraken.index', compact('afspraken'));
        } catch (\Exception $e) {
            // Technische fout opslaan in Laravel log.
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
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken toevoegen.');
        }

        // Server-side validatie: voorkomt lege velden en verkeerde datum/tijd.
        $request->validate([
            'klant_id' => 'required|exists:klanten,id',
            'medewerker_id' => 'required|exists:medewerkers,id',
            'behandeling_id' => 'required|exists:behandelingen,id',
            'datum' => 'required|date|after:today',
            'starttijd' => 'required',
            'eindtijd' => 'required|after:starttijd',
        ], [
            'required' => 'Vul alle verplichte velden in.',
            'datum.after' => 'De afspraak moet minimaal één dag na vandaag ingepland worden.',
            'eindtijd.after' => 'De eindtijd moet later zijn dan de starttijd.',
        ]);

        try {
            // Afspraak opslaan via model.
            Afspraak::create([
                'klant_id' => $request->klant_id,
                'medewerker_id' => $request->medewerker_id,
                'behandeling_id' => $request->behandeling_id,
                'datum' => $request->datum,
                'starttijd' => $request->starttijd,
                'eindtijd' => $request->eindtijd,
                'status' => Afspraak::STATUS_GEPLAND,
                'opmerking' => $request->opmerking,
            ]);

            return redirect()->route('afspraken.index')
                ->with('success', 'Afspraak is succesvol toegevoegd.');
        } catch (\Exception $e) {
            Log::error('Fout bij toevoegen afspraak: ' . $e->getMessage());

            return back()->withInput()
                ->with('error', 'De afspraak kon niet worden toegevoegd.');
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
        if (!$this->isMedewerker()) {
            return redirect()->route('afspraken.index')
                ->with('error', 'Alleen medewerkers mogen afspraken wijzigen.');
        }

        // Server-side validatie bij wijzigen.
        $request->validate([
            'klant_id' => 'required|exists:klanten,id',
            'medewerker_id' => 'required|exists:medewerkers,id',
            'behandeling_id' => 'required|exists:behandelingen,id',
            'datum' => 'required|date|after:today',
            'starttijd' => 'required',
            'eindtijd' => 'required|after:starttijd',
        ], [
            'required' => 'Vul alle verplichte velden in.',
            'datum.after' => 'De afspraak moet minimaal één dag na vandaag ingepland worden.',
            'eindtijd.after' => 'De eindtijd moet later zijn dan de starttijd.',
        ]);

        try {
            $afspraak = Afspraak::find($id);

            if (!$afspraak) {
                return redirect()->route('afspraken.index')
                    ->with('error', 'Afspraak niet gevonden.');
            }

            // Afspraak wijzigen via model.
            $afspraak->update([
                'klant_id' => $request->klant_id,
                'medewerker_id' => $request->medewerker_id,
                'behandeling_id' => $request->behandeling_id,
                'datum' => $request->datum,
                'starttijd' => $request->starttijd,
                'eindtijd' => $request->eindtijd,
                'status' => Afspraak::STATUS_GEWIJZIGD,
                'opmerking' => $request->opmerking,
            ]);

            return redirect()->route('afspraken.index')
                ->with('success', 'De afspraak is succesvol gewijzigd.');
        } catch (\Exception $e) {
            Log::error('Fout bij wijzigen afspraak: ' . $e->getMessage());

            return back()->withInput()
                ->with('error', 'De afspraak kon niet worden gewijzigd.');
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
