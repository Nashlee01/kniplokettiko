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

                                    <button type="button" class="delete-btn" onclick="openDeleteModal({{ $klant->id }}, '{{ $klant->voornaam }} {{ $klant->achternaam }}')">
                                        🗑
                                    </button>
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

<!-- ================================================================
     DELETE CONFIRMATION MODAL (Centered, Custom HTML)
     ================================================================ -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Klant verwijderen</h2>
        <p>Weet je zeker dat je <strong id="klantNaam"></strong> wilt verwijderen?</p>

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
// Paramaters:
// - klantId: ID van klant om te verwijderen
// - klantNaam: Naam om in modal te tonen
function openDeleteModal(klantId, klantNaam) {
    document.getElementById('klantNaam').textContent = klantNaam;
    // Set form action naar /klanten/{id} (Laravel DELETE route)
    document.getElementById('deleteForm').action = `/klanten/${klantId}`;
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
