@extends('layouts.app')

@section('content')
<div class="form-page">
    <div class="form-card">
        <h1>Afspraak wijzigen</h1>

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

        {{-- novalidate: onze eigen JS-validatie geeft betere feedback dan de standaard browserberichten --}}
        <form action="{{ route('afspraken.update', $afspraak->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('afspraken.partials.form', ['buttonText' => 'OPSLAAN'])
        </form>
    </div>
</div>
@endsection
