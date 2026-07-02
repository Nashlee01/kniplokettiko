@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="form-card">
        <h1>Klant wijzigen</h1>

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('klanten.update', $klant->id) }}" method="POST">
            @csrf
            @method('PUT')

            <label for="voornaam">Voornaam</label>
            <input id="voornaam" type="text" name="voornaam" value="{{ old('voornaam', $klant->voornaam) }}" maxlength="50" autocomplete="given-name" required>

            <label for="achternaam">Achternaam</label>
            <input id="achternaam" type="text" name="achternaam" value="{{ old('achternaam', $klant->achternaam) }}" maxlength="50" autocomplete="family-name" required>

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $klant->email) }}" maxlength="100" autocomplete="email" required>

            <label for="telefoon">Telefoon</label>
            <input id="telefoon" type="tel" name="telefoon" value="{{ old('telefoon', $klant->telefoon) }}" maxlength="20" pattern="[0-9+\-\s]{8,20}" autocomplete="tel" required>

            <label for="straatnaam">Straatnaam</label>
            <input id="straatnaam" type="text" name="straatnaam" value="{{ old('straatnaam', $adres->straatnaam ?? '') }}" maxlength="50" autocomplete="address-line1" required>

            <label for="huisnummer">Huisnummer</label>
            <input id="huisnummer" type="number" name="huisnummer" value="{{ old('huisnummer', $adres->huisnummer ?? '') }}" min="1" step="1" required>

            <label for="postcode">Postcode</label>
            <input id="postcode" type="text" name="postcode" value="{{ old('postcode', $adres->postcode ?? '') }}" maxlength="10" pattern="[0-9]{4}\s?[A-Za-z]{2}" autocomplete="postal-code" required>

            <label for="plaats">Plaats</label>
            <input id="plaats" type="text" name="plaats" value="{{ old('plaats', $adres->plaats ?? '') }}" maxlength="50" autocomplete="address-level2" required>

            <div class="form-actions">
                <a href="{{ route('klanten.index') }}" class="btn cancel">ANNULEREN</a>
                <button type="submit" class="btn primary">OPSLAAN</button>
            </div>
        </form>
    </div>
</div>
@endsection
