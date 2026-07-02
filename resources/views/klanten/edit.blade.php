@extends('layouts.app')

@section('content')
<div class="dashboard-shell">
    <aside class="dashboard-sidebar">
        <div class="brand-block">
            <div class="brand-logo">✂</div>
            <div>
                <strong>KNIPLOKET</strong>
                <small>T I K O</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('home') }}" class="sidebar-link">Dashboard</a>
            <a href="#" class="sidebar-link">Afspraken</a>
            <a href="#" class="sidebar-link">Producten</a>
            <a href="{{ route('klanten.index') }}" class="sidebar-link active">Klanten</a>
            <a href="#" class="sidebar-link">Instellingen</a>
        </nav>

        <div class="sidebar-user">
            <div class="user-name">{{ $gebruikerNaam ?: 'Gebruiker' }}</div>
            <div class="user-role">{{ $gebruikerRol ?: 'Rol' }}</div>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <a href="#" class="topbar-btn">AFSPRAAK</a>
            <a href="#" class="topbar-btn">PRODUCTEN</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="topbar-btn">UITLOGGEN</button>
            </form>
        </header>

        <section class="content-card">
            <div class="card-header-row">
                <h1>Klant wijzigen</h1>
                <a href="{{ route('klanten.index') }}" class="secondary-card-btn">Terug naar overzicht</a>
            </div>

            @if(session('error'))
                <div class="error">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('klanten.update', $klant->id) }}" method="POST" class="customer-form-grid">
                @csrf
                @method('PUT')

                <div>
                    <label for="voornaam">Voornaam</label>
                    <input id="voornaam" type="text" name="voornaam" value="{{ old('voornaam', $klant->voornaam) }}" maxlength="50" autocomplete="given-name" required>
                </div>

                <div>
                    <label for="achternaam">Achternaam</label>
                    <input id="achternaam" type="text" name="achternaam" value="{{ old('achternaam', $klant->achternaam) }}" maxlength="50" autocomplete="family-name" required>
                </div>

                <div>
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $klant->email) }}" maxlength="100" autocomplete="email" required>
                </div>

                <div>
                    <label for="telefoon">Telefoon</label>
                    <input id="telefoon" type="tel" name="telefoon" value="{{ old('telefoon', $klant->telefoon) }}" maxlength="20" pattern="[0-9+\-\s]{8,20}" autocomplete="tel" required>
                </div>

                <div>
                    <label for="straatnaam">Straatnaam</label>
                    <input id="straatnaam" type="text" name="straatnaam" value="{{ old('straatnaam', $adres->straatnaam ?? '') }}" maxlength="50" autocomplete="address-line1" required>
                </div>

                <div>
                    <label for="huisnummer">Huisnummer</label>
                    <input id="huisnummer" type="number" name="huisnummer" value="{{ old('huisnummer', $adres->huisnummer ?? '') }}" min="1" step="1" required>
                </div>

                <div>
                    <label for="postcode">Postcode</label>
                    <input id="postcode" type="text" name="postcode" value="{{ old('postcode', $adres->postcode ?? '') }}" maxlength="10" pattern="[0-9]{4}\s?[A-Za-z]{2}" autocomplete="postal-code" required>
                </div>

                <div>
                    <label for="plaats">Plaats</label>
                    <input id="plaats" type="text" name="plaats" value="{{ old('plaats', $adres->plaats ?? '') }}" maxlength="50" autocomplete="address-level2" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="primary-card-btn">Opslaan</button>
                </div>
            </form>
        </section>
    </main>
</div>
@endsection
