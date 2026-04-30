@extends('layouts.app')

@section('title', 'Impostazioni')
@section('meta_description', 'Imposta la lingua preferita per vedere i contenuti nella tua lingua.')

@section('content')
    <div class="container py-5" style="max-width: 900px;">
        <div class="mb-4">
            <h1 class="text-white">Impostazioni utente</h1>
            <p class="text-secondary">Scegli la lingua da usare per le informazioni TCG e le pagine dell'app.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card bg-dark text-light border-secondary rounded-4 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="language" class="form-label text-secondary">Lingua preferita</label>
                        <select id="language" name="language" class="form-select bg-black border-secondary text-white">
                            @foreach ($languages as $code => $label)
                                <option value="{{ $code }}" @selected($currentLanguage === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('language')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Salva impostazioni</button>
                </form>
            </div>
        </div>
    </div>
@endsection
