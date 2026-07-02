@extends('layouts.app')

@section('content')

<div class="auth-page">

    <div class="auth-card">

        <h1>Registreren</h1>

        <p>Maak een nieuw account aan.</p>

        {{-- Succesmelding --}}
        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Algemene foutmelding --}}
        @if(session('error'))
            <div class="error">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">

            @csrf

            <label>Naam</label>
            <input
                type="text"
                name="naam"
                value="{{ old('naam') }}"
                placeholder="Voer je naam in">

            @error('naam')
                <small>{{ $message }}</small>
            @enderror


            <label>E-mailadres</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Voer je e-mailadres in">

            @error('email')
                <small>{{ $message }}</small>
            @enderror


            <label>Wachtwoord</label>
            <input
                type="password"
                name="wachtwoord"
                placeholder="Voer een wachtwoord in">

            @error('wachtwoord')
                <small>{{ $message }}</small>
            @enderror


            <label>Bevestig wachtwoord</label>
            <input
                type="password"
                name="wachtwoord_confirmation"
                placeholder="Herhaal je wachtwoord">


            <button type="submit">
                Registreren
            </button>

        </form>

        <hr>

        <p style="text-align:center;">
            Heb je al een account?
        </p>

        <a href="{{ route('login') }}">
            Inloggen
        </a>

    </div>

</div>

@endsection
