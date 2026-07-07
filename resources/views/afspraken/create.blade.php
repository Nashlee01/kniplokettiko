@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="form-card">
        <h1>Afspraak toevoegen</h1>

        @if($errors->any())
            <div class="error">
                <strong>❌ Fout bij opslaan:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <form action="{{ route('afspraken.store') }}" method="POST">
            @csrf

            @include('afspraken.partials.form', ['buttonText' => 'BEVESTIGEN'])
        </form>
    </div>
</div>
@endsection
