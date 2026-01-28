<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /**
     * Register a new user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'] ?? explode('@', $validated['email'])[0],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Create token for the user
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrazione completata con successo',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'display_name' => $user->display_name,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Credenziali non valide.'],
            ]);
        }

        $user = Auth::user();

        // Revoke all previous tokens (optional - rimuovi questa riga se vuoi permettere login multipli)
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login effettuato con successo',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'display_name' => $user->display_name,
            ],
            'token' => $token,
        ], 200);
    }

    /**
     * Logout user (revoke current token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the current user's token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout effettuato con successo',
        ], 200);
    }

    /**
     * Get current authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'display_name' => $user->display_name,
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
            ],
        ], 200);
    }
}
