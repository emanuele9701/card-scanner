@extends('layouts.app')

@section('title', 'Modifica Profilo')

@push('styles')
<style>
    .edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .avatar-edit-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 1.5rem;
    }

    .avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--pokemon-yellow);
    }

    .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--pokemon-blue) 0%, var(--pokemon-dark-blue) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #fff;
        border: 4px solid var(--pokemon-yellow);
    }

    .avatar-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
        cursor: pointer;
    }

    .avatar-edit-container:hover .avatar-overlay {
        opacity: 1;
    }

    .form-section {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--pokemon-yellow);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-control {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        border-radius: 10px;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        border-color: var(--pokemon-yellow) !important;
        box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.2) !important;
    }

    .form-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .alert-success-custom {
        background: rgba(40, 167, 69, 0.15);
        border: 1px solid rgba(40, 167, 69, 0.3);
        border-radius: 12px;
        color: #28a745;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Header -->
        <div class="edit-header">
            <div>
                <h1 class="page-title mb-1">
                    <i class="bi bi-pencil-square me-2"></i>Modifica Profilo
                </h1>
                <p class="page-subtitle mb-0">Aggiorna le tue informazioni personali</p>
            </div>
            <a href="{{ route('profile.show') }}" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-2"></i>Indietro
            </a>
        </div>

        @if(session('success'))
        <div class="alert-success-custom">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        </div>
        @endif

        <!-- Avatar Section -->
        <div class="glass-card p-4 mb-4 text-center">
            <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                @csrf
                <div class="avatar-edit-container">
                    @if($user->avatar)
                    <img src="{{ $user->avatar_url }}" alt="Avatar" class="avatar">
                    @else
                    <div class="avatar-placeholder">
                        <i class="bi bi-person"></i>
                    </div>
                    @endif
                    <label for="avatarInput" class="avatar-overlay">
                        <i class="bi bi-camera-fill text-white fs-4"></i>
                    </label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" class="d-none" onchange="document.getElementById('avatarForm').submit()">
                </div>
                <p class="text-white-50 small mb-0">Clicca sull'immagine per cambiare avatar</p>
            </form>
        </div>

        <!-- Personal Info Form -->
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="glass-card p-4 mb-4">
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-person-badge"></i> Informazioni Personali
                    </h3>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                name="first_name" value="{{ old('first_name', $user->first_name) }}"
                                placeholder="Il tuo nome">
                            @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cognome</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                name="last_name" value="{{ old('last_name', $user->last_name) }}"
                                placeholder="Il tuo cognome">
                            @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Data di Nascita</label>
                            <input type="date" class="form-control @error('birth_date') is-invalid @enderror"
                                name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                            @error('birth_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                name="phone" value="{{ old('phone', $user->phone) }}"
                                placeholder="+39 123 456 7890">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-pokemon">
                        <i class="bi bi-check-lg me-2"></i>Salva Modifiche
                    </button>
                </div>
            </div>
        </form>

        <!-- Change Password Form -->
        <form action="{{ route('profile.password') }}" method="POST" id="password">
            @csrf
            @method('PUT')

            <div class="glass-card p-4">
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-shield-lock"></i> Cambia Password
                    </h3>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Password Attuale</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                name="current_password" placeholder="La tua password attuale">
                            @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nuova Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                name="password" placeholder="Minimo 8 caratteri">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Conferma Nuova Password</label>
                            <input type="password" class="form-control"
                                name="password_confirmation" placeholder="Ripeti la nuova password">
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="bi bi-key me-2"></i>Cambia Password
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection