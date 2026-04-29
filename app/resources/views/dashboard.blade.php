@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Dashboard — panoramica della tua collezione di carte.')

@section('content')
    <style>
        .todo-box {
            border: 1px dashed rgba(255, 255, 255, 0.1);
            background-color: rgba(255, 255, 255, 0.02);
            border-radius: 1rem;
        }

        .todo-icon-wrap {
            width: 56px;
            height: 56px;
            background-color: rgba(245, 158, 11, 0.1);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <div class="container py-5" style="max-width: 1280px;">

        {{-- Page Header --}}
        <div class="mb-5">
            <h1 class="text-white fw-bold mb-1" style="font-size:1.875rem; letter-spacing:-0.02em;">Dashboard</h1>
            <p class="text-secondary mb-0" style="font-size:0.875rem;">Panoramica della tua collezione</p>
        </div>

        {{-- TODO Card --}}
        <div class="todo-box d-flex align-items-center justify-content-center p-5">
            <div class="d-flex flex-column align-items-center gap-3 text-center">
                <div class="todo-icon-wrap">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#fbbf24"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.42 15.17l-5.384 3.084A1 1 0 015 17.387V5.613a1 1 0 011.036-.867l.087.007 5.297 3.032M16.5 3.75V8.25m0 0l3 3m-3-3l-3 3m3 5.25v4.5m0 0l3-3m-3 3l-3-3" />
                    </svg>
                </div>
                <div>
                    <p class="text-white fw-semibold mb-1" style="font-size:1.25rem;">TODO</p>
                    <p class="text-secondary mb-0" style="font-size:0.875rem;">Il contenuto della dashboard verrà
                        implementato a breve.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
