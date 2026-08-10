<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthWebController extends Controller
{
    /**
     * Mostra il form di login.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Effettua il login tramite sessione web.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Credenziali non valide.',
        ])->onlyInput('email');
    }

    /**
     * Mostra il form di registrazione.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Registra un nuovo utente con username e password.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255', 'unique:users,name'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'Lo username è obbligatorio.',
            'name.unique'        => 'Questo username è già in uso.',
            'name.max'           => 'Lo username non può superare 255 caratteri.',
            'password.required'  => 'La password è obbligatoria.',
            'password.min'       => 'La password deve avere almeno 8 caratteri.',
            'password.confirmed' => 'Le password non coincidono.',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => strtolower($request->name) . '@placeholder.local',
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Effettua il logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
