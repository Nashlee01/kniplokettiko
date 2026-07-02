@extends('layouts.app')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <h1>Inloggen</h1>
        <p>Log in om afspraken en klanten te beheren.</p>

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <label>E-mailadres</label>
            <input type="email" name="email" value="{{ old('email') }}">

            @error('email')
                <small>{{ $message }}</small>
            @enderror

            <label>Wachtwoord</label>
            <input type="password" name="wachtwoord">

            @error('wachtwoord')
                <small>{{ $message }}</small>
            @enderror

            <button type="submit">Inloggen</button>
        </form>

        <a href="{{ route('register') }}">Nog geen account? Registreren</a>
    </div>
</div>
@endsection
