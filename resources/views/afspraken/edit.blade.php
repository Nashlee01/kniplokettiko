@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="form-card">
        <h1>Afspraak wijzigen</h1>

        @if($errors->any())
            <div class="error">Vul alle verplichte velden in.</div>
        @endif

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <form action="{{ route('afspraken.update', $afspraak->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('afspraken.partials.form', ['buttonText' => 'OPSLAAN'])
        </form>
    </div>
</div>
@endsection