<?php

namespace App\Http\Controllers;

use App\Services\DashboardCacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Mostra la dashboard con le statistiche della collezione dell'utente.
     * I dati sono serviti dalla cache e invalidati automaticamente
     * quando la collezione cambia o i prezzi vengono aggiornati.
     */
    public function index(Request $request): View
    {
        $data = app(DashboardCacheService::class)->getForUser($request->user()->id);

        return view('dashboard', $data);
    }
}

