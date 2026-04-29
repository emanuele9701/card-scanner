{{-- Se hai una welcome page, eccola con Bootstrap 5 --}}
@extends('layouts.guest')

@section('title', 'Benvenuto')

@section('content')
    <div class="d-flex align-items-center justify-content-center min-vh-100">
        <div class="text-center">
            <h1 class="text-white fw-bold mb-3">Card Scanner</h1>
            <a href="{{ route('login') }}" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold">Accedi</a>
        </div>
    </div>
@endsection
