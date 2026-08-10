<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Imposta la locale dell'app in base alla preferenza dell'utente autenticato.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $locale = Auth::user()->language;

            if (in_array($locale, ['it', 'en'])) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }
}
