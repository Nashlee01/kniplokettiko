@extends('layouts.app')

@section('content')
<div class="dashboard-page">
    <aside class="sidebar">
        <h2>✂ KNIPLOKET<br>TIKO</h2>

        <nav>
            <a href="{{ route('home') }}">Home</a>
            <a class="active" href="{{ route('afspraken.index') }}">Afspraken</a>

            @if(in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
                <a href="{{ route('klanten.index') }}">Klanten</a>
                <a href="#">Producten</a>
            @endif
        </nav>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit">Uitloggen</button>
        </form>

        {{-- Alleen medewerkers zien de gebruikerskaart --}}
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
        <h1>Afspraken bekijken</h1>

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <div class="info-card">
            <div class="info-icon">📅</div>
            <div>
                <h2>Afspraken overzicht</h2>

                @if(in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
                    <p>Bekijk hieronder alle gemaakte afspraken. Je kunt een afspraak bekijken, wijzigen of verwijderen.</p>
                @else
                    <p>Bekijk hieronder jouw eigen afspraken.</p>
                @endif
            </div>
        </div>

        <div class="card">
            @if(in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
                <a href="{{ route('afspraken.create') }}" class="btn primary right-button">
                    + Nieuwe afspraak
                </a>
            @endif

            @if($afspraken->isEmpty())
                <p>Er zijn momenteel geen afspraken beschikbaar.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Datum & tijd</th>
                            <th>Klant</th>
                            <th>Dienst</th>
                            <th>Status</th>
                            <th>Acties</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($afspraken as $afspraak)
                            <tr>
                                <td>
                                    <strong>{{ $afspraak->datum }}</strong><br>
                                    {{ $afspraak->starttijd }} - {{ $afspraak->eindtijd }}
                                </td>

                                <td>
                                    {{ $afspraak->klant }}<br>
                                    <small>Medewerker: {{ $afspraak->medewerker }}</small>
                                </td>

                                <td>
                                    {{ $afspraak->behandeling }}<br>

                                    @if(isset($afspraak->prijs))
                                        € {{ number_format($afspraak->prijs, 2, ',', '.') }}
                                    @else
                                        Geen prijs
                                    @endif
                                </td>

                                <td>
                                    <span class="status">{{ $afspraak->status }}</span>
                                </td>

                                <td class="actions">
                                    <a href="{{ route('afspraken.show', $afspraak->id) }}">👁</a>

                                    {{-- Alleen medewerkers mogen wijzigen en verwijderen --}}
                                    @if(in_array(session('gebruiker_rol'), ['Medewerker', 'Eigenaar', 'Receptionist']))
                                        <a href="{{ route('afspraken.edit', $afspraak->id) }}">✏️</a>

                                        <form action="{{ route('afspraken.destroy', $afspraak->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Weet je zeker dat je deze afspraak wilt verwijderen?')">
                                                🗑
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </main>
</div>
@endsection