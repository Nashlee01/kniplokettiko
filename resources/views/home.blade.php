@extends('layouts.app')

@section('content')
<div class="home-page">

    {{-- Hoofdnavigatie met snelle link naar klantbeheer. --}}
    <nav class="home-navbar">
        <div class="logo">
            <span class="logo-icon">✂</span>
            <div>
                <strong>KNIPLOKET</strong>
                <small>T I K O</small>
            </div>
        </div>

        <div class="nav-buttons">
            <a href="#" class="nav-btn">AFSPRAAK</a>
            <a href="#" class="nav-btn">PRODUCTEN</a>
            <a href="{{ route('klanten.index') }}" class="nav-btn">KLANTEN</a>

            @if(session('gebruiker_id'))
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="nav-btn" type="submit">UITLOGGEN</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-btn">INLOGGEN</a>
            @endif
        </div>
    </nav>

    {{-- Hero-sectie met primaire acties. --}}
    <section class="hero">
        <div class="hero-text">
            <h1>Jouw look,<br>onze passie.</h1>
            <p>Maak een afspraak of<br>bestel je favoriete producten.</p>

            <div class="hero-buttons">
                <a href="#" class="btn primary">📅 AFSPRAAK MAKEN</a>
                <a href="#" class="btn white">🛍 PRODUCTEN SHOPPEN</a>
            </div>
        </div>
    </section>

    {{-- USP-blokken onderaan de homepage. --}}
    <section class="feature-bar">
        <div class="feature">
            <span>👤</span>
            <p>Professionele<br>stylisten</p>
        </div>

        <div class="feature">
            <span>🛍</span>
            <p>Hoogwaardige<br>producten</p>
        </div>

        <div class="feature">
            <span>💬</span>
            <p>Persoonlijk<br>advies</p>
        </div>

        <div class="feature">
            <span>✂</span>
            <p>Altijd een look<br>die bij jou past</p>
        </div>
    </section>

    <a href="#" class="chat-button">💬</a>

</div>
@endsection
