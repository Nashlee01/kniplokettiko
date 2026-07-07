@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="form-card">
        <h1>Klant wijzigen</h1>

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="error">
                <strong>❌ Fout bij opslaan:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Client-side foutoverzicht bovenaan het formulier (gevuld door JavaScript) --}}
        <div id="clientErrorBox" style="display: none; background: #fde8e8; border: 1px solid #d32f2f; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; color: #d32f2f;">
            <strong>❌ Controleer de volgende velden:</strong>
            <ul id="clientErrorList" style="margin: 8px 0 0 20px;"></ul>
        </div>

        <form action="{{ route('klanten.update', $klant->id) }}" method="POST" id="klantForm">
            @csrf
            @method('PUT')

            <label for="voornaam">Voornaam</label>
            <input id="voornaam" type="text" name="voornaam" value="{{ old('voornaam', $klant->voornaam) }}" maxlength="50" autocomplete="given-name" required>

            <label for="achternaam">Achternaam</label>
            <input id="achternaam" type="text" name="achternaam" value="{{ old('achternaam', $klant->achternaam) }}" maxlength="50" autocomplete="family-name" required>

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $klant->email) }}" maxlength="100" autocomplete="email" required>
            <div id="emailError" class="error-message" style="display: none; color: #d32f2f; margin-top: 5px;">
                ⚠️ Voer een geldig e-mailadres in
            </div>

            <label for="telefoon">Telefoon</label>
            <input id="telefoon" type="tel" name="telefoon" value="{{ old('telefoon', $klant->telefoon) }}" maxlength="20" pattern="[0-9+\-\s]{8,20}" autocomplete="tel" required>
            <div id="telefoonError" class="error-message" style="display: none; color: #d32f2f; margin-top: 5px;">
                ⚠️ Telefoonnummer moet minimaal 8 cijfers bevatten
            </div>

            <label for="straatnaam">Straatnaam</label>
            <input id="straatnaam" type="text" name="straatnaam" value="{{ old('straatnaam', $adres->straatnaam ?? '') }}" maxlength="50" autocomplete="address-line1" required>

            <label for="huisnummer">Huisnummer</label>
            <input id="huisnummer" type="number" name="huisnummer" value="{{ old('huisnummer', $adres->huisnummer ?? '') }}" min="1" step="1" required>

            <label for="postcode">Postcode</label>
            <input id="postcode" type="text" name="postcode" value="{{ old('postcode', $adres->postcode ?? '') }}" maxlength="10" pattern="[0-9]{4}\s?[A-Za-z]{2}" autocomplete="postal-code" required>
            <div id="postcodeError" class="error-message" style="display: none; color: #d32f2f; margin-top: 5px;">
                ⚠️ Postcode moet format hebben: 1234 AB
            </div>

            <label for="plaats">Plaats</label>
            <input id="plaats" type="text" name="plaats" value="{{ old('plaats', $adres->plaats ?? '') }}" maxlength="50" autocomplete="address-level2" required>

            <div class="form-actions">
                <a href="{{ route('klanten.index') }}" class="btn cancel">ANNULEREN</a>
                <button type="submit" class="btn primary" id="submitBtn">OPSLAAN</button>
            </div>
        </form>

        <script>
            /**
             * ================================================================
             * CLIENT-SIDE VALIDATIE: Klant formulier
             * ================================================================
             * Doel: Directe feedback geven zonder server round-trip
             *
             * Validaties:
             * 1. Email format (moet geldig e-mailadres zijn)
             * 2. Telefoon format (minimaal 8 cijfers/tekens)
             * 3. Postcode format (1234 AB)
             * 4. Velden mogen niet leeg of alleen spaties zijn
             *
             * Voordeel: Gebruiker krijgt instant feedback bij fouten
             * ================================================================
             */

            const form = document.getElementById('klantForm');
            const emailInput = document.getElementById('email');
            const telefoonInput = document.getElementById('telefoon');
            const postcodeInput = document.getElementById('postcode');
            const emailError = document.getElementById('emailError');
            const telefoonError = document.getElementById('telefoonError');
            const postcodeError = document.getElementById('postcodeError');
            const submitBtn = document.getElementById('submitBtn');
            const clientErrorBox = document.getElementById('clientErrorBox');
            const clientErrorList = document.getElementById('clientErrorList');

            /**
             * Valideer email format
             * @returns {boolean} true als geldig
             */
            function validateEmail() {
                if (!emailInput.value) {
                    emailError.style.display = 'none';
                    return true; // required attribute zal dit afvangen
                }

                // Simpele email validatie (HTML5 validation doet meer)
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value)) {
                    emailError.style.display = 'block';
                    return false;
                } else {
                    emailError.style.display = 'none';
                    return true;
                }
            }

            /**
             * Valideer telefoonnummer format
             * @returns {boolean} true als geldig
             */
            function validateTelefoon() {
                if (!telefoonInput.value) {
                    telefoonError.style.display = 'none';
                    return true;
                }

                // Tel moet minimaal 8 tekens/cijfers bevatten (geen spaties)
                const digitsOnly = telefoonInput.value.replace(/[\s\-\+]/g, '');
                if (digitsOnly.length < 8) {
                    telefoonError.style.display = 'block';
                    return false;
                } else {
                    telefoonError.style.display = 'none';
                    return true;
                }
            }

            /**
             * Valideer postcode format
             * @returns {boolean} true als geldig
             */
            function validatePostcode() {
                if (!postcodeInput.value) {
                    postcodeError.style.display = 'none';
                    return true;
                }

                // Format: 1234 AB of 1234AB
                const postcodeRegex = /^[0-9]{4}\s?[A-Za-z]{2}$/;
                if (!postcodeRegex.test(postcodeInput.value)) {
                    postcodeError.style.display = 'block';
                    return false;
                } else {
                    postcodeError.style.display = 'none';
                    return true;
                }
            }

            /**
             * Toon een overzicht van alle fouten bovenaan het formulier
             */
            function updateErrorBox() {
                const fouten = [];

                if (!validateEmail() && emailInput.value) fouten.push('Voer een geldig e-mailadres in');
                if (!validateTelefoon() && telefoonInput.value) fouten.push('Telefoonnummer moet minimaal 8 cijfers bevatten');
                if (!validatePostcode() && postcodeInput.value) fouten.push('Postcode moet format hebben: 1234 AB');

                clientErrorList.innerHTML = fouten.map(f => `<li>${f}</li>`).join('');
                clientErrorBox.style.display = fouten.length > 0 ? 'block' : 'none';
            }

            /**
             * Valideer het hele formulier en zet submit button state
             */
            function validateForm() {
                const emailValid = validateEmail();
                const telefoonValid = validateTelefoon();
                const postcodeValid = validatePostcode();

                // Disable submit button als er validatie fouten zijn
                const allValid = emailValid && telefoonValid && postcodeValid;
                submitBtn.disabled = !allValid;
                submitBtn.style.opacity = allValid ? '1' : '0.6';
                submitBtn.style.cursor = allValid ? 'pointer' : 'not-allowed';

                // Foutenoverzicht bovenaan bijwerken
                updateErrorBox();
            }

            // Real-time validatie bij veldwijzigingen
            emailInput.addEventListener('change', validateForm);
            emailInput.addEventListener('input', validateForm);
            telefoonInput.addEventListener('change', validateForm);
            telefoonInput.addEventListener('input', validateForm);
            postcodeInput.addEventListener('change', validateForm);
            postcodeInput.addEventListener('input', validateForm);

            // Validatie bij form submit
            form.addEventListener('submit', function(e) {
                if (!validateEmail() || !validateTelefoon() || !validatePostcode()) {
                    e.preventDefault();
                }
            });

            // Initiële validatie bij laden
            validateForm();
        </script>

    </div>
</div>
@endsection
