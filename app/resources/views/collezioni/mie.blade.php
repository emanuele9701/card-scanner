@extends('layouts.app')

@section('title', 'Le mie collezioni')
@section('meta_description', 'Visualizza e gestisci le tue collezioni di carte.')

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
            background-color: rgba(99, 102, 241, 0.1);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <div class="container py-5" style="max-width: 1280px;">

        <div class="mb-5">
            <h1 class="text-white fw-bold mb-1" style="font-size:1.875rem; letter-spacing:-0.02em;">Le mie collezioni</h1>
            <p class="text-secondary mb-0" style="font-size:0.875rem;">Gestisci le tue collezioni personali</p>
        </div>

        <div class="todo-box d-flex align-items-center justify-content-center p-5">
            <div class="d-flex flex-column align-items-center gap-3 text-center">
                @empty($collezioni)
                    <div class="todo-icon-wrap">
                        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#818cf8"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-white fw-semibold mb-1" style="font-size:1.25rem;">Non hai collezioni salvata</p>
                    </div>
                @endempty
                @if (!empty($collezioni))
                    @foreach ($collezioni as $collezione)
                    @endforeach
                @endif
            </div>
        </div>

    </div>
@endsection
