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

                                        <button type="button" class="delete-btn" onclick="openDeleteModal({{ $afspraak->id }}, '{{ $afspraak->klant }} - {{ $afspraak->datum }}')">
                                            🗑
                                        </button>
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

<!-- ================================================================
     DELETE CONFIRMATION MODAL (Centered, Custom HTML)
     ================================================================ -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Afspraak verwijderen</h2>
        <p>Weet je zeker dat je de afspraak van <strong id="afspraakNaam"></strong> wilt verwijderen?</p>

        <div class="modal-buttons">
            <button type="button" class="btn cancel" onclick="closeDeleteModal()">Annuleren</button>

            <form id="deleteForm" action="" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Verwijderen</button>
            </form>
        </div>
    </div>
</div>

<style>
/* ================================================================
   MODAL STYLING: Centered overlay met backdrop
   ================================================================ */
.modal {
    /* Fixed positioning: altijd zichtbaar, ook bij scrollen */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;

    /* Donker backdrop (semi-transparent) */
    background-color: rgba(0, 0, 0, 0.5);

    /* Flexbox: centeert de modal content horizontaal EN verticaal */
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    text-align: center;
}

.modal-content h2 {
    margin: 0 0 15px 0;
    color: #333;
}

.modal-content p {
    color: #666;
    margin-bottom: 30px;
}

.modal-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.modal-buttons .btn {
    flex: 1;
    padding: 10px 20px;
}

.btn.danger {
    background-color: #dc3545;
    color: white;
}

.btn.danger:hover {
    background-color: #c82333;
}

.delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
}
</style>

<script>
// ================================================================
// openDeleteModal(): Open de delete confirmation modal
// ================================================================
// Parameters:
// - afspraakId: ID van afspraak om te verwijderen
// - afspraakNaam: Description (klant - datum) om in modal te tonen
function openDeleteModal(afspraakId, afspraakNaam) {
    document.getElementById('afspraakNaam').textContent = afspraakNaam;
    // Set form action naar /afspraken/{id} (Laravel DELETE route)
    document.getElementById('deleteForm').action = `/afspraken/${afspraakId}`;
    // Toon modal (flexbox centeert het automatisch)
    document.getElementById('deleteModal').style.display = 'flex';
}

// ================================================================
// closeDeleteModal(): Sluit de modal
// ================================================================
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// ================================================================
// Backdrop Click Handler: Sluit modal bij click buiten content
// ================================================================
// User klikt op donker gedeelte → modal sluit
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
@endsection
