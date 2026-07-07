{{-- Client-side foutoverzicht bovenaan het formulier (gevuld door JavaScript) --}}
<div id="clientErrorBox" style="display: none; background: #fde8e8; border: 1px solid #d32f2f; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; color: #d32f2f;">
    <strong>❌ Controleer de volgende velden:</strong>
    <ul id="clientErrorList" style="margin: 8px 0 0 20px;"></ul>
</div>

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
<select name="klant_id" required id="klantSelect">
    <option value="">Selecteer klant</option>

    @foreach($klanten as $klant)
        <option value="{{ $klant->id }}"
            {{ old('klant_id', $afspraak->klant_id ?? '') == $klant->id ? 'selected' : '' }}>
            {{ $klant->voornaam }} {{ $klant->achternaam }}
        </option>
    @endforeach
</select>
<div id="klantError" class="error-message" style="display: none; color: #d32f2f; margin-top: 5px;">
    ⚠️ Deze klant moet een adres hebben
</div>

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
{{-- Client-side validatie: bij aanmaken mag datum pas vanaf morgen; bij wijzigen mag de bestaande datum blijven --}}
<input
    type="date"
    name="datum"
    id="datumInput"
    value="{{ old('datum', $afspraak->datum ?? '') }}"
    min="{{ isset($afspraak->id) ? ($afspraak->datum ?? now()->addDay()->format('Y-m-d')) : now()->addDay()->format('Y-m-d') }}"
    required>
<div id="datumError" class="error-message" style="display: none; color: #d32f2f; margin-top: 5px;">
    ⚠️ Datum moet in de toekomst liggen
</div>

<label>Starttijd</label>
<input
    type="time"
    name="starttijd"
    id="starttijd"
    value="{{ old('starttijd', $afspraak->starttijd ?? '') }}"
    required>

<label>Eindtijd</label>
<input
    type="time"
    name="eindtijd"
    id="eindtijd"
    value="{{ old('eindtijd', $afspraak->eindtijd ?? '') }}"
    required>
<div id="timeError" class="error-message" style="display: none; color: #d32f2f; margin-top: 5px;">
    ⚠️ Eindtijd moet na starttijd liggen
</div>

<label>Opmerkingen</label>
<textarea name="opmerking" placeholder="Eventuele opmerking...">{{ old('opmerking', $afspraak->opmerking ?? '') }}</textarea>

<div class="form-actions">
    <a href="{{ route('afspraken.index') }}" class="btn cancel">ANNULEREN</a>
    <button type="submit" class="btn primary" id="submitBtn">{{ $buttonText }}</button>
</div>

<script>
    /**
     * ================================================================
     * CLIENT-SIDE VALIDATIE: Afspraken formulier
     * ================================================================
     * Doel: Directe feedback geven zonder server round-trip
     *
     * Validaties:
     * 1. Eindtijd > Starttijd (eindtijd moet NA starttijd liggen)
     * 2. Klant selectie (geen lege optie)
     *
     * Voordeel: Gebruiker krijgt instant feedback bij fouten
     * ================================================================
     */
    const starttijdInput = document.getElementById('starttijd');
    const eindtijdInput = document.getElementById('eindtijd');
    const datumInput = document.getElementById('datumInput');
    const timeErrorDiv = document.getElementById('timeError');
    const datumErrorDiv = document.getElementById('datumError');
    const klantSelect = document.getElementById('klantSelect');
    const klantErrorDiv = document.getElementById('klantError');
    const submitBtn = document.getElementById('submitBtn');
    const form = submitBtn.closest('form');
    const clientErrorBox = document.getElementById('clientErrorBox');
    const clientErrorList = document.getElementById('clientErrorList');

    /**
     * Valideer of eindtijd groter is dan starttijd
     * @returns {boolean} true als geldig, false als ongeldig
     */
    function validateTimes() {
        // Als één van beide velden leeg is, dan is het OK (required zal afvangen)
        if (!starttijdInput.value || !eindtijdInput.value) {
            timeErrorDiv.style.display = 'none';
            return true;
        }

        // Eindtijd moet GROTER zijn dan starttijd
        if (eindtijdInput.value <= starttijdInput.value) {
            timeErrorDiv.style.display = 'block';
            return false;
        } else {
            timeErrorDiv.style.display = 'none';
            return true;
        }
    }

    /**
     * Valideer klant selectie
     * @returns {boolean} true als geldig
     */
    function validateKlant() {
        // Klant kan niet leeg zijn (required), dus enkel OK als geselecteerd
        if (klantSelect.value === '') {
            klantErrorDiv.style.display = 'none';
            return true;
        }
        klantErrorDiv.style.display = 'none';
        return true;
    }

    /**
     * Valideer datum (mag niet in het verleden liggen)
     * @returns {boolean} true als geldig
     */
    function validateDatum() {
        if (!datumInput || !datumInput.value) {
            datumErrorDiv.style.display = 'none';
            return true;
        }

        const gekozenDatum = new Date(datumInput.value);
        const minDatum = new Date(datumInput.min);

        if (gekozenDatum < minDatum) {
            datumErrorDiv.style.display = 'block';
            return false;
        } else {
            datumErrorDiv.style.display = 'none';
            return true;
        }
    }

    /**
     * Toon een overzicht van alle fouten bovenaan het formulier
     */
    function updateErrorBox() {
        const fouten = [];

        if (!validateTimes() && starttijdInput.value && eindtijdInput.value) {
            fouten.push('Eindtijd moet na starttijd liggen');
        }
        if (!validateDatum() && datumInput && datumInput.value) {
            fouten.push('Datum moet in de toekomst liggen');
        }

        clientErrorList.innerHTML = fouten.map(f => `<li>${f}</li>`).join('');
        clientErrorBox.style.display = fouten.length > 0 ? 'block' : 'none';
    }

    /**
     * Controleer alle validaties en zet submit button state
     */
    function validateForm() {
        const timesValid = validateTimes();
        const datumValid = validateDatum();
        const klantValid = validateKlant();

        // Disable submit button als één van de validaties faalt
        const allValid = timesValid && datumValid;
        submitBtn.disabled = !allValid;
        submitBtn.style.opacity = allValid ? '1' : '0.6';
        submitBtn.style.cursor = allValid ? 'pointer' : 'not-allowed';

        // Foutenoverzicht bovenaan bijwerken
        updateErrorBox();
    }

    // Real-time validatie bij wijzigingen
    starttijdInput.addEventListener('change', validateForm);
    starttijdInput.addEventListener('input', validateForm);
    eindtijdInput.addEventListener('change', validateForm);
    eindtijdInput.addEventListener('input', validateForm);
    if (datumInput) {
        datumInput.addEventListener('change', validateForm);
        datumInput.addEventListener('input', validateForm);
    }
    klantSelect.addEventListener('change', validateForm);

    // Validatie bij form submit — vangt ook Enter-toets en andere submit-methodes af
    form.addEventListener('submit', function(e) {
        if (!validateTimes() || !validateDatum()) {
            e.preventDefault();
        }
    });

    // Initiële validatie wanneer formulier geladen wordt
    validateForm();
</script>
