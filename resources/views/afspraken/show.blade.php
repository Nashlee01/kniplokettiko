@extends('layouts.app')

@section('content')
<div class="dashboard-page">
    <aside class="sidebar">
        <h2>✂ KNIPLOKET<br>TIKO</h2>

        <nav>
            <a href="{{ route('home') }}">Home</a>
            <a class="active" href="{{ route('afspraken.index') }}">Afspraken</a>

            @if(in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
                <a href="#">Klanten</a>
                <a href="#">Producten</a>
            @endif
        </nav>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit">Uitloggen</button>
        </form>

        @if(session('gebruiker_id') && in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    {{ strtoupper(substr(session('gebruiker_naam'), 0, 1)) }}
                </div>

                <div class="sidebar-user-info">
                    <strong>{{ session('gebruiker_naam') }}</strong>
                    <span>{{ session('gebruiker_email') }}</span>
                    <small>{{ session('gebruiker_rol') }}</small>
                </div>
            </div>
        @endif
    </aside>

    <main class="dashboard-content">
        <h1>Afspraak bekijken</h1>

        <div class="card details-card">
            <h2>Afspraak details</h2>

            <p><strong>Klant:</strong> {{ $afspraak->klant }}</p>
            <p><strong>Medewerker:</strong> {{ $afspraak->medewerker }}</p>
            <p><strong>Behandeling:</strong> {{ $afspraak->behandeling }}</p>
            <p><strong>Datum:</strong> {{ $afspraak->datum }}</p>
            <p><strong>Starttijd:</strong> {{ $afspraak->starttijd }}</p>
            <p><strong>Eindtijd:</strong> {{ $afspraak->eindtijd }}</p>
            <p><strong>Status:</strong> {{ $afspraak->status }}</p>
            <p><strong>Duur:</strong> {{ $afspraak->duur }} minuten</p>
            <p><strong>Prijs:</strong> € {{ number_format($afspraak->prijs, 2, ',', '.') }}</p>
            <p><strong>Opmerking:</strong> {{ $afspraak->opmerking ?? 'Geen opmerking' }}</p>

            <div class="form-actions">
                <a href="{{ route('afspraken.index') }}" class="btn cancel">Terug naar overzicht</a>

                @if(in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
                    <a href="{{ route('afspraken.edit', $afspraak->id) }}" class="btn primary">
                        Afspraak wijzigen
                    </a>
                @endif
            </div>
        </div>
    </main>
</div>
@endsection