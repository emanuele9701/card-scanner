@extends('layouts.app')

@section('title', 'Le mie collezioni')
@section('meta_description', 'Visualizza e gestisci le tue collezioni di carte.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-10">
        <h1 class="text-3xl font-bold tracking-tight text-white">Le mie collezioni</h1>
        <p class="mt-2 text-sm text-gray-500">Gestisci le tue collezioni personali</p>
    </div>

    <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/[0.1] bg-white/[0.02] p-16">
        <div class="flex flex-col items-center gap-4 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10">
                <svg class="h-7 w-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-semibold text-white">TODO</p>
                <p class="mt-1 text-sm text-gray-500">Le tue collezioni appariranno qui.</p>
            </div>
        </div>
    </div>
</div>
@endsection
