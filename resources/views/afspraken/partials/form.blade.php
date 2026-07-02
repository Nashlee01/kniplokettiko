<label>Behandeling</label>
<select name="behandeling_id" required>
    <option value="">Selecteer behandeling</option>

    @foreach($behandelingen as $behandeling)
        <option value="{{ $behandeling->id }}"
            {{ old('behandeling_id', $afspraak->behandeling_id ?? '') == $behandeling->id ? 'selected' : '' }}>
            {{ $behandeling->naam }}
        </option>
    @endforeach
</select>

<label>Klant</label>
<select name="klant_id" required>
    <option value="">Selecteer klant</option>

    @foreach($klanten as $klant)
        <option value="{{ $klant->id }}"
            {{ old('klant_id', $afspraak->klant_id ?? '') == $klant->id ? 'selected' : '' }}>
            {{ $klant->voornaam }} {{ $klant->achternaam }}
        </option>
    @endforeach
</select>

<label>Medewerker</label>
<select name="medewerker_id" required>
    <option value="">Selecteer medewerker</option>

    @foreach($medewerkers as $medewerker)
        <option value="{{ $medewerker->id }}"
            {{ old('medewerker_id', $afspraak->medewerker_id ?? '') == $medewerker->id ? 'selected' : '' }}>
            {{ $medewerker->voornaam }} {{ $medewerker->achternaam }}
        </option>
    @endforeach
</select>

<label>Datum</label>
{{-- Client-side validatie: datum mag pas vanaf morgen gekozen worden --}}
<input
    type="date"
    name="datum"
    value="{{ old('datum', $afspraak->datum ?? '') }}"
    min="{{ now()->addDay()->format('Y-m-d') }}"
    required>

<label>Starttijd</label>
<input
    type="time"
    name="starttijd"
    value="{{ old('starttijd', $afspraak->starttijd ?? '') }}"
    required>

<label>Eindtijd</label>
<input
    type="time"
    name="eindtijd"
    value="{{ old('eindtijd', $afspraak->eindtijd ?? '') }}"
    required>

<label>Opmerkingen</label>
<textarea name="opmerking" placeholder="Eventuele opmerking...">{{ old('opmerking', $afspraak->opmerking ?? '') }}</textarea>

<div class="form-actions">
    <a href="{{ route('afspraken.index') }}" class="btn cancel">ANNULEREN</a>
    <button type="submit" class="btn primary">{{ $buttonText }}</button>
</div>