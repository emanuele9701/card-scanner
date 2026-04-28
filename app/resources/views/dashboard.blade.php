@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Dashboard — panoramica della tua collezione di carte.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Page Header --}}
    <div class="mb-10">
        <h1 class="text-3xl font-bold tracking-tight text-white">Dashboard</h1>
        <p class="mt-2 text-sm text-gray-500">Panoramica della tua collezione</p>
    </div>

    {{-- TODO Card --}}
    <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/[0.1] bg-white/[0.02] p-16">
        <div class="flex flex-col items-center gap-4 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10">
                <svg class="h-7 w-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11.42 15.17l-5.384 3.084A1 1 0 015 17.387V5.613a1 1 0 011.036-.867l.087.007 5.297 3.032M16.5 3.75V8.25m0 0l3 3m-3-3l-3 3m3 5.25v4.5m0 0l3-3m-3 3l-3-3"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-semibold text-white">TODO</p>
                <p class="mt-1 text-sm text-gray-500">Il contenuto della dashboard verrà implementato a breve.</p>
            </div>
        </div>
    </div>
</div>
@endsection
