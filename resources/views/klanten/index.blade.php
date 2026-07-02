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
                <h1>Klant overzicht</h1>
                <a href="{{ route('klanten.create') }}" class="primary-card-btn">+ NIEUWE KLANT</a>
            </div>

            @if(session('success'))
                <div class="success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error">{{ session('error') }}</div>
            @endif

            <form action="{{ route('klanten.index') }}" method="GET" class="filter-row">
                <input type="text" name="q" value="{{ $search }}" placeholder="Zoek klant...">
                <button type="submit" class="filter-btn">Zoeken</button>
            </form>

            <div class="table-wrap">
                <table class="customer-table">
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
                    @forelse($klanten as $klant)
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
                            <td class="actions-col">
                                <a href="{{ route('klanten.edit', $klant->id) }}" class="icon-btn blue" title="Bewerken">✎</a>
                                <button type="button" class="icon-btn red" disabled title="Verwijderen">🗑</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Er zijn geen klanten gevonden</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer-row">
                <div>Toont {{ $klanten->firstItem() ?? 0 }} tot {{ $klanten->lastItem() ?? 0 }} van {{ $klanten->total() }} klanten</div>
                <div>{{ $klanten->links() }}</div>
            </div>

            <div class="info-banner">
                @if($klanten->total() === 0)
                    Er zijn geen klanten gevonden
                @else
                    Er zijn {{ $klanten->total() }} klanten geregistreerd.
                @endif
            </div>
        </section>
    </main>
</div>
@endsection
