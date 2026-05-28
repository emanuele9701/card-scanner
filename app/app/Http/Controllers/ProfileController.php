<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Mostra la pagina del profilo utente.
     */
    public function index(): View
    {
        return view('profile');
    }

    /**
     * Aggiorna lo username dell'utente.
     */
    public function updateUsername(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:users,name,' . $user->id],
        ], [
            'name.required' => 'Lo username è obbligatorio.',
            'name.unique'   => 'Questo username è già in uso.',
            'name.max'      => 'Lo username non può superare 255 caratteri.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray())
                ->errorBag('username');
        }

        // Aggiorna anche il placeholder email per mantenerlo coerente
        $user->name  = $request->name;
        $user->email = strtolower($request->name) . '@placeholder.local';
        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('success', __('Username aggiornato con successo.'));
    }

    /**
     * Aggiorna la password dell'utente.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Verifica la password attuale
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('La password attuale non è corretta.'),
            ])->errorBag('password');
        }

        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.required'  => 'La nuova password è obbligatoria.',
            'new_password.min'       => 'La nuova password deve avere almeno 8 caratteri.',
            'new_password.confirmed' => 'Le password non coincidono.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray())
                ->errorBag('password');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('success', __('Password aggiornata con successo.'));
    }
}

