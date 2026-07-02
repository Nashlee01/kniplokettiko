@extends('layouts.app')

@section('content')
<div class="dashboard-page">
    <aside class="sidebar">
        <h2>✂ KNIPLOKET<br>TIKO</h2>

        <nav>
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('afspraken.index') }}">Afspraken</a>
            <a class="active" href="{{ route('klanten.index') }}">Klanten</a>
            <a href="#">Producten</a>
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
        <h1>Klanten bekijken</h1>

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <div class="info-card">
            <div class="info-icon">👥</div>
            <div>
                <h2>Klanten overzicht</h2>
                <p>Bekijk hieronder alle klanten. Je kunt een klant toevoegen, wijzigen of verwijderen.</p>
            </div>
        </div>

        <div class="card">
            <a href="{{ route('klanten.create') }}" class="btn primary right-button">+ Nieuwe klant</a>

            <form action="{{ route('klanten.index') }}" method="GET" class="form-actions">
                <input type="text" name="q" value="{{ $search }}" placeholder="Zoek klant...">
                <button type="submit" class="btn cancel">Zoeken</button>
            </form>

            @if($klanten->isEmpty())
                <p>Er zijn geen klanten gevonden</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Naam</th>
                            <th>Telefoon</th>
                            <th>E-mail</th>
                            <th>Adres</th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($klanten as $klant)
                            <tr>
                                <td>{{ $klant->id }}</td>
                                <td>{{ $klant->voornaam }} {{ $klant->achternaam }}</td>
                                <td>{{ $klant->telefoon }}</td>
                                <td>{{ $klant->email }}</td>
                                <td>
                                    @if($klant->straatnaam)
                                        {{ $klant->straatnaam }} {{ $klant->huisnummer }}<br>
                                        {{ $klant->postcode }} {{ $klant->plaats }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="actions">
                                    <a href="{{ route('klanten.edit', $klant->id) }}">✏️</a>

                                    <form action="{{ route('klanten.destroy', $klant->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            onclick="return confirm('Weet je zeker dat je deze klant wilt verwijderen?')">
                                            🗑
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top: 14px;">
                    {{ $klanten->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </main>
</div>
@endsection
