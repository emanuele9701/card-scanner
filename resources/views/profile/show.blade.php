@extends('layouts.app')

@section('title', 'Il Mio Profilo')

@push('styles')
<style>
    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .avatar-container {
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
        box-shadow: 0 4px 20px rgba(255, 203, 5, 0.3);
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

    .profile-name {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.25rem;
    }

    .profile-email {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.95rem;
    }

    .profile-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .profile-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--pokemon-yellow);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .profile-row {
        display: flex;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .profile-row:last-child {
        border-bottom: none;
    }

    .profile-label {
        width: 140px;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.9rem;
    }

    .profile-value {
        flex: 1;
        color: #fff;
        font-size: 0.95rem;
    }

    .profile-value.empty {
        color: rgba(255, 255, 255, 0.3);
        font-style: italic;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pokemon-yellow);
    }

    .stat-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Profile Header -->
        <div class="glass-card p-4 mb-4">
            <div class="profile-header">
                <div class="avatar-container">
                    @if($user->avatar)
                    <img src="{{ $user->avatar_url }}" alt="Avatar" class="avatar">
                    @else
                    <div class="avatar-placeholder">
                        <i class="bi bi-person"></i>
                    </div>
                    @endif
                </div>
                <h1 class="profile-name">{{ $user->full_name }}</h1>
                <p class="profile-email">{{ $user->email }}</p>

                <a href="{{ route('profile.edit') }}" class="btn btn-pokemon mt-3">
                    <i class="bi bi-pencil me-2"></i>Modifica Profilo
                </a>
            </div>

            <!-- Stats -->
            @php
            $totalCards = \App\Models\PokemonCard::count();
            $completedCards = \App\Models\PokemonCard::where('status', 'completed')->count();
            @endphp
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">{{ $totalCards }}</div>
                    <div class="stat-label">Carte Totali</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ $completedCards }}</div>
                    <div class="stat-label">Complete</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ $user->created_at->diffInDays(now()) }}</div>
                    <div class="stat-label">Giorni Attivo</div>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="profile-card">
            <h3 class="profile-card-title">
                <i class="bi bi-person-badge"></i> Informazioni Personali
            </h3>

            <div class="profile-row">
                <span class="profile-label">Nome</span>
                <span class="profile-value {{ !$user->first_name ? 'empty' : '' }}">
                    {{ $user->first_name ?? 'Non specificato' }}
                </span>
            </div>

            <div class="profile-row">
                <span class="profile-label">Cognome</span>
                <span class="profile-value {{ !$user->last_name ? 'empty' : '' }}">
                    {{ $user->last_name ?? 'Non specificato' }}
                </span>
            </div>

            <div class="profile-row">
                <span class="profile-label">Data di Nascita</span>
                <span class="profile-value {{ !$user->birth_date ? 'empty' : '' }}">
                    {{ $user->birth_date ? $user->birth_date->format('d/m/Y') : 'Non specificata' }}
                </span>
            </div>

            <div class="profile-row">
                <span class="profile-label">Telefono</span>
                <span class="profile-value {{ !$user->phone ? 'empty' : '' }}">
                    {{ $user->phone ?? 'Non specificato' }}
                </span>
            </div>
        </div>

        <!-- Account Info -->
        <div class="profile-card">
            <h3 class="profile-card-title">
                <i class="bi bi-shield-lock"></i> Sicurezza Account
            </h3>

            <div class="profile-row">
                <span class="profile-label">Email</span>
                <span class="profile-value">{{ $user->email }}</span>
            </div>

            <div class="profile-row">
                <span class="profile-label">Membro dal</span>
                <span class="profile-value">{{ $user->created_at->format('d F Y') }}</span>
            </div>

            <div class="profile-row">
                <span class="profile-label">Password</span>
                <span class="profile-value">
                    ••••••••
                    <a href="{{ route('profile.edit') }}#password" class="ms-2 text-warning small">Cambia</a>
                </span>
            </div>
        </div>
    </div>
</div>
@endsection