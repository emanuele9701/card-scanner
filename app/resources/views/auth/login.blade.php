@extends('layouts.guest')

@section('title', 'Accedi')
@section('meta_description', 'Accedi al tuo account Card Scanner per gestire la tua collezione.')

@section('content')
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">

    {{-- Animated background blobs --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="animate-blob absolute -left-32 -top-32 h-96 w-96 rounded-full bg-red-500/10 blur-3xl"></div>
        <div class="animate-blob animation-delay-2000 absolute -right-32 top-1/3 h-96 w-96 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="animate-blob animation-delay-4000 absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
    </div>

    {{-- Login Card --}}
    <div class="relative z-10 w-full max-w-md">

        {{-- Logo --}}
        <div class="mb-8 flex flex-col items-center">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 shadow-2xl shadow-red-500/30">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Card Scanner</h1>
            <p class="mt-1 text-sm text-gray-500">Accedi alla tua collezione</p>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl border border-white/[0.08] bg-gray-900/60 p-8 shadow-2xl shadow-black/40 backdrop-blur-xl">

            {{-- Error Messages --}}
            @if($errors->any())
                <div id="login-errors" class="mb-6 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3">
                    @foreach($errors->all() as $error)
                        <p class="text-sm font-medium text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-300">Email</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input type="email" name="email" id="email" required autocomplete="email" autofocus
                               value="{{ old('email') }}"
                               placeholder="nome@esempio.com"
                               class="block w-full rounded-xl border border-white/[0.08] bg-white/[0.04] py-3 pl-10 pr-4 text-sm text-white placeholder-gray-600 shadow-sm transition-all duration-200 focus:border-red-500/40 focus:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-red-500/20">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-300">Password</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" name="password" id="password" required autocomplete="current-password"
                               placeholder="••••••••"
                               class="block w-full rounded-xl border border-white/[0.08] bg-white/[0.04] py-3 pl-10 pr-4 text-sm text-white placeholder-gray-600 shadow-sm transition-all duration-200 focus:border-red-500/40 focus:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-red-500/20">
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" id="login-submit"
                        class="group relative flex w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-red-500 to-rose-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/25 transition-all duration-300 hover:shadow-xl hover:shadow-red-500/30 hover:brightness-110 active:scale-[0.98]">
                    <span class="relative z-10">Accedi</span>
                    <svg class="relative z-10 h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    {{-- Shine effect --}}
                    <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 group-hover:translate-x-full"></div>
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-center text-xs text-gray-600">
            &copy; {{ date('Y') }} Card Scanner — Tutti i diritti riservati
        </p>
    </div>
</div>
@endsection
